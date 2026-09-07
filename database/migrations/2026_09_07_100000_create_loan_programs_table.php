<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('set_id')->constrained('sets')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 64);
            // string, not enum: on Postgres an enum renders as a CHECK constraint
            // that a later state addition can only remove with driver-specific SQL.
            $table->string('status', 16)->default('draft');
            $table->string('currency', 3)->default('NGN');

            // Whole naira, matching dues.amount / payments.amount.
            $table->integer('fund_pool')->default(0);
            $table->integer('min_loan_amount')->default(0);
            $table->integer('max_loan_amount')->nullable();

            $table->unsignedTinyInteger('default_tenure_months')->default(3);
            $table->unsignedTinyInteger('max_tenure_months')->default(3);

            // Basis points: 500 = 5.00%. A decimal rate rounds differently
            // between Postgres numeric and sqlite REAL.
            $table->unsignedInteger('interest_rate_bps')->default(0);
            $table->string('interest_method', 24)->default('flat_per_tenure');

            $table->unsignedTinyInteger('required_guarantors')->default(2);
            $table->unsignedSmallInteger('grace_days')->default(7);
            $table->json('reminder_days_before')->nullable();
            $table->unsignedTinyInteger('max_active_loans_per_member')->default(1);
            $table->unsignedInteger('min_member_tenure_days')->nullable();

            $table->boolean('penalty_enabled')->default(false);
            $table->integer('penalty_flat_amount')->nullable();
            $table->unsignedInteger('penalty_rate_bps')->nullable();
            $table->string('penalty_basis', 16)->nullable();

            $table->boolean('forum_publication_enabled')->default(true);
            $table->boolean('public_publication_allowed')->default(false);
            $table->unsignedSmallInteger('publication_expiry_days')->default(90);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['set_id', 'slug']);
            $table->index(['set_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_programs');
    }
};
