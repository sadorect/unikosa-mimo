<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncAssetsToR2 extends Command
{
    protected $signature = 'assets:sync-r2
        {--from=public : Source disk to copy from}
        {--to=r2 : Destination disk to copy to}
        {--overwrite : Re-copy files that already exist on the destination}
        {--dry-run : List what would be copied without writing anything}';

    protected $description = 'Copy every file from the local public disk to R2 (idempotent, resumable).';

    public function handle(): int
    {
        $fromName = $this->option('from');
        $toName = $this->option('to');
        $dry = (bool) $this->option('dry-run');
        $overwrite = (bool) $this->option('overwrite');

        $from = Storage::disk($fromName);
        $to = Storage::disk($toName);

        $files = $from->allFiles();
        $this->info(sprintf('%d file(s) found on the "%s" disk.', count($files), $fromName));

        if ($files === []) {
            return self::SUCCESS;
        }

        $copied = $skipped = $failed = 0;
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $file) {
            // Dry-run previews purely from the source, never touching the
            // destination (which may not be configured yet).
            if ($dry) {
                $this->line("  [dry-run] would copy: {$file}");
                $copied++;
                $bar->advance();
                continue;
            }

            if (! $overwrite && $to->exists($file)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            try {
                $stream = $from->readStream($file);
                $to->writeStream($file, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $copied++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("  FAILED {$file}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info(sprintf(
            '%sCopied: %d   Skipped (already present): %d   Failed: %d',
            $dry ? '[dry-run] ' : '',
            $copied,
            $skipped,
            $failed,
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
