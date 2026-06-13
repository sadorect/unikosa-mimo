<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('company')->nullable();
            $table->text('description');
            $table->enum('type', ['full_time', 'part_time', 'contract', 'internship', 'remote']);
            $table->string('location')->nullable();
            $table->boolean('is_remote')->default(false);
            $table->integer('salary_min')->nullable();
            $table->integer('salary_max')->nullable();
            $table->string('salary_currency')->nullable();
            $table->string('application_url')->nullable();
            $table->string('contact_email')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement("ALTER TABLE alumni_jobs ADD COLUMN search_vector tsvector");
        DB::statement("CREATE INDEX alumni_jobs_search_idx ON alumni_jobs USING GIN(search_vector)");
        DB::statement("CREATE TRIGGER alumni_jobs_search_update BEFORE INSERT OR UPDATE ON alumni_jobs FOR EACH ROW EXECUTE PROCEDURE tsvector_update_trigger(search_vector, 'pg_catalog.english', title, description)");
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_jobs');
    }
};
