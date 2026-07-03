<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            // Set once, atomically, when the results-notification job claims the election —
            // makes that job idempotent against double-clicks and queue retries.
            $table->timestamp('results_notified_at')->nullable()->after('results_published_at');
        });
    }

    public function down(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->dropColumn('results_notified_at');
        });
    }
};
