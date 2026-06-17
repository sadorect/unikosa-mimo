<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE forum_posts ADD COLUMN search_vector tsvector");
            DB::statement("CREATE INDEX forum_posts_search_idx ON forum_posts USING GIN(search_vector)");
            DB::statement("CREATE TRIGGER forum_posts_search_update BEFORE INSERT OR UPDATE ON forum_posts FOR EACH ROW EXECUTE PROCEDURE tsvector_update_trigger(search_vector, 'pg_catalog.english', title, body)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_posts');
    }
};
