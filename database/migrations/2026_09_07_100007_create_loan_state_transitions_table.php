<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only machine log. The Auditable trait already writes a generic
        // field diff to activity_log, which is fine for compliance and poor for
        // rendering a member-facing timeline or asking how long loans sit in
        // review. Both are kept, deliberately.
        Schema::create('loan_state_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_type', 16)->default('user');
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['loan_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_state_transitions');
    }
};
