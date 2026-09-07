<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('set_coordinators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('set_id')->constrained('sets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['set_id', 'user_id']);
            $table->index('user_id');
        });

        // Carry the existing single representative over as the first coordinator
        // so nothing is lost when approvals start being scoped.
        $reps = DB::table('sets')->whereNotNull('rep_id')->whereNull('deleted_at')->get(['id', 'rep_id']);

        foreach ($reps as $set) {
            if (! DB::table('users')->where('id', $set->rep_id)->exists()) {
                continue;
            }

            DB::table('set_coordinators')->insertOrIgnore([
                'set_id' => $set->id,
                'user_id' => $set->rep_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('set_coordinators');
    }
};
