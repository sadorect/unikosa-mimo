<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected', 'changes_requested'])
                ->default('approved')
                ->after('created_by');
            $table->text('feedback')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('feedback')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['status', 'feedback', 'reviewed_by', 'reviewed_at']);
        });
    }
};
