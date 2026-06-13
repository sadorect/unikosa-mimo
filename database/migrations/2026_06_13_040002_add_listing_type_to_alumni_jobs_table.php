<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumni_jobs', function (Blueprint $table) {
            $table->enum('listing_type', ['job', 'hire_alumnus'])
                  ->default('job')
                  ->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('alumni_jobs', function (Blueprint $table) {
            $table->dropColumn('listing_type');
        });
    }
};
