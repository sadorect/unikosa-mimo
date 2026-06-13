<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('imported')->default(false)->after('chapter_id');
            $table->boolean('account_claimed')->default(false)->after('imported');
            $table->timestamp('imported_at')->nullable()->after('account_claimed');
            $table->timestamp('claimed_at')->nullable()->after('imported_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['imported', 'account_claimed', 'imported_at', 'claimed_at']);
        });
    }
};
