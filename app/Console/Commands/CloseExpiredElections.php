<?php

namespace App\Console\Commands;

use App\Models\Election;
use Illuminate\Console\Command;

class CloseExpiredElections extends Command
{
    protected $signature = 'elections:close-expired';

    protected $description = 'Transition elections whose voting window has passed from voting_open to closed.';

    public function handle(): int
    {
        $closed = Election::where('status', 'voting_open')
            ->whereNotNull('voting_end_at')
            ->where('voting_end_at', '<', now())
            ->update(['status' => 'closed']);

        $this->info("Closed {$closed} election(s) with an expired voting window.");

        return self::SUCCESS;
    }
}
