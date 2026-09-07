<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The association decided every default notice stays inside the gated
     * forum: naming a member on the open internet is not a sanction it wants
     * to hold. So the two publication audiences are now the defaulter's own
     * set (the default) and the whole association (a deliberate escalation) —
     * both members-only.
     */
    public function up(): void
    {
        Schema::table('loan_programs', function (Blueprint $table) {
            $table->renameColumn('forum_publication_enabled', 'set_forum_publication_enabled');
            $table->renameColumn('public_publication_allowed', 'association_forum_publication_enabled');
        });

        // There is no public defaulters page to switch on any more.
        DB::table('settings')->where('key', 'public_defaulters_page_enabled')->delete();
    }

    public function down(): void
    {
        Schema::table('loan_programs', function (Blueprint $table) {
            $table->renameColumn('set_forum_publication_enabled', 'forum_publication_enabled');
            $table->renameColumn('association_forum_publication_enabled', 'public_publication_allowed');
        });
    }
};
