<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A guarantor who pays on a defaulting borrower's behalf is discharging
        // someone else's debt, and the ledger has to say so: `recorded_by` is
        // only whoever typed the row in (often a treasurer), which is a
        // different question from whose money it was.
        Schema::table('loan_repayments', function (Blueprint $table) {
            $table->foreignId('paid_by_user_id')->nullable()->after('loan_id')->constrained('users')->nullOnDelete();
            $table->string('payer_role', 16)->default('borrower')->after('paid_by_user_id');
        });

        // Everything recorded before this migration was the borrower's own.
        DB::table('loan_repayments')
            ->whereNull('paid_by_user_id')
            ->update(['payer_role' => 'borrower']);
    }

    public function down(): void
    {
        Schema::table('loan_repayments', function (Blueprint $table) {
            $table->dropForeign(['paid_by_user_id']);
            $table->dropColumn(['paid_by_user_id', 'payer_role']);
        });
    }
};
