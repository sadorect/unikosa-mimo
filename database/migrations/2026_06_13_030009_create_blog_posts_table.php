<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->text('body');
            $table->string('featured_image')->nullable();
            $table->enum('status', ['draft', 'pending', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE blog_posts ADD COLUMN search_vector tsvector");
            DB::statement("CREATE INDEX blog_posts_search_idx ON blog_posts USING GIN(search_vector)");
            DB::statement("CREATE TRIGGER blog_posts_search_update BEFORE INSERT OR UPDATE ON blog_posts FOR EACH ROW EXECUTE PROCEDURE tsvector_update_trigger(search_vector, 'pg_catalog.english', title, excerpt, body)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
