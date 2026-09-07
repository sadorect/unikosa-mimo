<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Who reviews and who disburses, per fund. Spatie permissions are global
        // (no teams package) and set_coordinators is about membership approval,
        // not money — so the review team needs its own pivot. As with
        // set_coordinators, this pivot is the authority for authorisation
        // checks; the role is only what opens the admin panel.
        Schema::create('loan_program_officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_program_id')->constrained('loan_programs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 16);
            $table->timestamps();

            $table->unique(['loan_program_id', 'user_id', 'role'], 'loan_program_officers_unique');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_program_officers');
    }
};
