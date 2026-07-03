<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hardens ballot secrecy. The original votes table used an auto-increment id plus
 * cast_at/created_at timestamps. Because each ballot's votes are written in the same
 * transaction as that voter's receipt, those columns let anyone with DB access
 * correlate a receipt (which identifies the voter) with its votes — by timestamp
 * proximity, or by rank-ordering both tables on their monotonic ids. This recreates
 * votes with a random UUID primary key and NO time columns, so a vote row carries
 * nothing that can be ordered against, or joined to, a voter receipt.
 *
 * Safe to drop-and-recreate: no real election has run, so votes holds no data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('votes');

        Schema::create('votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('is_abstention')->default(false);
            // No timestamps and no cast_at: a vote must not record when (or in what order)
            // it was cast, or it becomes correlatable to a timestamped voter receipt.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');

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
};
