<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_default_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->string('scope', 16);
            $table->foreignId('forum_post_id')->nullable()->constrained('forum_posts')->nullOnDelete();
            $table->string('public_slug')->nullable()->unique();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            // Not nullable: every notice carries its own takedown clock.
            $table->timestamp('expires_at');
            $table->timestamp('taken_down_at')->nullable();
            $table->foreignId('taken_down_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('takedown_reason')->nullable();

            // The crux of the design: the published page renders this frozen,
            // minimised snapshot verbatim and never reads live loan data, so a
            // later change to the loan record cannot silently start disclosing
            // something nobody authorised.
            $table->json('published_payload');

            $table->timestamps();

            // Single-active-per-scope is enforced in LoanPublicationService, not
            // by a partial unique index: partial indexes differ between Postgres
            // and sqlite and the test suite runs on sqlite.
            $table->index(['scope', 'taken_down_at', 'expires_at'], 'loan_publications_active_index');
            $table->index('loan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_default_publications');
    }
};
