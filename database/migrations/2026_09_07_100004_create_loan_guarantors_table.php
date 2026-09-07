<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();

            // Nullable now, though v1 requires an approved member: a future
            // "invite a non-member, they must register first" flow becomes a
            // code change rather than a migration.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invited_email')->nullable();
            $table->string('invited_name')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->string('status', 16)->default('invited');
            $table->timestamp('responded_at')->nullable();
            $table->text('response_note')->nullable();
            $table->timestamp('agreed_terms_at')->nullable();
            $table->string('agreed_ip', 45)->nullable();

            $table->timestamps();

            $table->unique(['loan_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['loan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_guarantors');
    }
};
