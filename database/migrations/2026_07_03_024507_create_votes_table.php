<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deliberately no user_id (or any other voter-identifying column) on this table.
     * Ballot secrecy depends on votes never being linkable back to the voter who cast
     * them — see election_voter_receipts for the separate (anonymous) turnout record.
     */
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('is_abstention')->default(false);
            $table->uuid('ballot_token')->unique();
            $table->timestamp('cast_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
