<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();

            // Identity. restrictOnDelete throughout: sqlite enforces cascades
            // (PRAGMA foreign_keys=ON), and deleting a member must never erase
            // the record of money they still owe.
            $table->string('reference', 24)->unique();
            $table->foreignId('loan_program_id')->constrained('loan_programs')->restrictOnDelete();
            // Denormalised from the program: every scoping query and every
            // Filament getEloquentQuery() filters on it.
            $table->foreignId('set_id')->constrained('sets')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            // Terms. Rate, tenure and guarantor count are snapshotted at
            // approval so later program edits never mutate a live loan.
            $table->integer('amount_requested');
            $table->integer('amount_approved')->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->unsignedTinyInteger('tenure_months')->nullable();
            $table->unsignedInteger('interest_rate_bps')->default(0);
            $table->integer('interest_amount')->nullable();
            $table->integer('total_repayable')->nullable();
            $table->text('purpose')->nullable();

            // Agreement to the terms of operation.
            $table->foreignId('loan_term_id')->nullable()->constrained('loan_terms')->nullOnDelete();
            $table->timestamp('terms_agreed_at')->nullable();
            $table->string('terms_agreed_ip', 45)->nullable();
            $table->string('terms_agreed_user_agent', 255)->nullable();

            $table->string('status', 32)->default('draft')->index();

            // Review.
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Guarantors.
            $table->unsignedTinyInteger('guarantors_required')->default(2);
            $table->timestamp('guarantors_secured_at')->nullable();

            // Disbursement — one tranche; tranching a welfare loan is YAGNI.
            $table->foreignId('disburser_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disburser_notified_at')->nullable();
            $table->timestamp('disburser_acknowledged_at')->nullable();
            $table->integer('disbursed_amount')->nullable();
            $table->date('disbursed_at')->nullable();
            $table->string('disbursement_method', 24)->nullable();
            $table->string('disbursement_reference')->nullable();
            $table->string('disbursement_proof_path')->nullable();
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();

            // The payback clock, started at disbursement.
            $table->date('due_at')->nullable();
            $table->date('grace_ends_at')->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->timestamp('guarantors_nudged_at')->nullable();

            // Balance and penalty. amount_repaid is a cache only — always
            // recomputed from confirmed repayments, never trusted.
            $table->integer('amount_repaid')->default(0);
            $table->integer('penalty_amount')->default(0);
            $table->text('penalty_reason')->nullable();
            $table->foreignId('penalty_applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('penalty_applied_at')->nullable();

            // Closure.
            $table->timestamp('defaulted_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('written_off_at')->nullable();
            $table->text('closure_note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['set_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'due_at']);
            $table->index(['status', 'grace_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
