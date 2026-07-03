<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamp('nominations_start_at')->nullable();
            $table->timestamp('nominations_end_at')->nullable();
            $table->timestamp('voting_start_at')->nullable();
            $table->timestamp('voting_end_at')->nullable();
            $table->enum('status', ['draft', 'nominations_open', 'voting_open', 'closed', 'results_published'])->default('draft');
            $table->timestamp('results_published_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elections');
    }
};
