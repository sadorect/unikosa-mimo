<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profile_privacy', function (Blueprint $table) {
            $table->boolean('notify_email_messages')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_privacy', function (Blueprint $table) {
            $table->dropColumn('notify_email_messages');
        });
    }
};
