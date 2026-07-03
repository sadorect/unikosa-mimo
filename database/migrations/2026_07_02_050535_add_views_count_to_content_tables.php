<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the views_count column each controller already increments
     * (Event, ForumPost, BlogPost, AlumniJob, BusinessListing) but which
     * was never actually migrated — caused a 500 on every detail view.
     */
    public function up(): void
    {
        foreach (['events', 'forum_posts', 'blog_posts', 'alumni_jobs', 'business_listings'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedInteger('views_count')->default(0);
            });
        }
    }

    public function down(): void
    {
        foreach (['events', 'forum_posts', 'blog_posts', 'alumni_jobs', 'business_listings'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('views_count');
            });
        }
    }
};
