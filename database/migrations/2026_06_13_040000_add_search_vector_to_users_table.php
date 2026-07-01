<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL full-text search; skipped on other drivers (e.g. sqlite in tests).
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE users ADD COLUMN IF NOT EXISTS search_vector tsvector");
        DB::statement("CREATE INDEX IF NOT EXISTS users_search_idx ON users USING GIN(search_vector)");
        DB::statement("
            CREATE OR REPLACE FUNCTION users_search_update() RETURNS trigger AS $$
            BEGIN
                NEW.search_vector :=
                    setweight(to_tsvector('pg_catalog.english', coalesce(NEW.name, '')), 'A') ||
                    setweight(to_tsvector('pg_catalog.english', coalesce(NEW.profession, '')), 'B') ||
                    setweight(to_tsvector('pg_catalog.english', coalesce(NEW.bio, '')), 'C');
                RETURN NEW;
            END
            $$ LANGUAGE plpgsql;
        ");
        DB::statement("
            CREATE TRIGGER users_search_update
            BEFORE INSERT OR UPDATE ON users
            FOR EACH ROW EXECUTE PROCEDURE users_search_update();
        ");
        DB::statement("
            UPDATE users SET search_vector =
                setweight(to_tsvector('pg_catalog.english', coalesce(name, '')), 'A') ||
                setweight(to_tsvector('pg_catalog.english', coalesce(profession, '')), 'B') ||
                setweight(to_tsvector('pg_catalog.english', coalesce(bio, '')), 'C')
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("DROP TRIGGER IF EXISTS users_search_update ON users");
        DB::statement("DROP FUNCTION IF EXISTS users_search_update()");
        DB::statement("DROP INDEX IF EXISTS users_search_idx");
        DB::statement("ALTER TABLE users DROP COLUMN IF EXISTS search_vector");
    }
};
