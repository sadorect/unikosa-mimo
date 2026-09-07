<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->integer('amount');
            // A date, not a timestamp: this is the day the bank transfer was
            // made, and no timezone should be able to move it.
            $table->date('paid_on');
            $table->string('method', 24)->default('bank_transfer');
            $table->string('reference')->nullable();
            $table->string('proof_path')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            // Only confirmed rows count toward the balance — an applicant must
            // not be able to clear their own default by declaring a payment.
            $table->string('status', 24)->default('pending_confirmation');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['loan_id', 'status']);
            $table->index('paid_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
    }
};
