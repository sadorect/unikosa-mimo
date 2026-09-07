<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Versioned terms of operation. A loan disputed in month five must
        // render the exact text its applicant agreed to, so editing the terms
        // must never retro-change a live loan.
        Schema::create('loan_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_program_id')->constrained('loan_programs')->cascadeOnDelete();
            $table->string('version', 32);
            $table->text('body');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['loan_program_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_terms');
    }
};
