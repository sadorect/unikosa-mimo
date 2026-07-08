<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RewriteAssetUrls extends Command
{
    protected $signature = 'assets:rewrite-urls
        {--from= : Old base URL prefix (defaults to APP_URL/storage)}
        {--to= : New base URL prefix (defaults to the r2 disk url)}
        {--dry-run : Report matching rows without updating anything}';

    protected $description = 'Rewrite stored absolute asset URLs (e.g. from the old /storage host to the R2 public URL).';

    /**
     * Columns that persist a fully-qualified asset URL built at upload time.
     */
    protected array $columns = [
        'users' => ['avatar'],
        'candidates' => ['photo'],
        'events' => ['cover_image'],
        'gallery_media' => ['path'],
        'job_applications' => ['cv_path'],
    ];

    public function handle(): int
    {
        $from = rtrim($this->option('from') ?: (rtrim((string) config('app.url'), '/') . '/storage'), '/');
        $to = rtrim($this->option('to') ?: (string) config('filesystems.disks.r2.url'), '/');

        if ($to === '') {
            $this->error('No destination base URL. Set R2_URL in the environment or pass --to=https://…');

            return self::FAILURE;
        }

        if ($from === $to) {
            $this->warn('Source and destination base URLs are identical — nothing to do.');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');
        $this->info(sprintf('Rewriting asset URLs:  "%s"  ->  "%s"', $from, $to));
        $this->newLine();

        $pdo = DB::getPdo();
        $fromQuoted = $pdo->quote($from);
        $toQuoted = $pdo->quote($to);
        $totalMatched = 0;
        $totalUpdated = 0;

        foreach ($this->columns as $table => $cols) {
            if (! Schema::hasTable($table)) {
                $this->warn(sprintf('  skip %-18s (table missing)', $table));
                continue;
            }

            foreach ($cols as $col) {
                if (! Schema::hasColumn($table, $col)) {
                    $this->warn(sprintf('  skip %-18s %-14s (column missing)', $table, $col));
                    continue;
                }

                $query = DB::table($table)->where($col, 'like', $from . '%');
                $matched = $query->count();
                $totalMatched += $matched;

                $this->line(sprintf('  %-18s %-14s %d row(s)', $table, $col, $matched));

                if (! $dry && $matched > 0) {
                    $totalUpdated += $query->update([
                        $col => DB::raw("REPLACE({$col}, {$fromQuoted}, {$toQuoted})"),
                    ]);
                }
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%sMatched: %d row(s)%s',
            $dry ? '[dry-run] ' : '',
            $totalMatched,
            $dry ? '' : "   Updated: {$totalUpdated} row(s)",
        ));

        if ($dry) {
            $this->comment('Run without --dry-run to apply. Copy files first with: php artisan assets:sync-r2');
        }

        return self::SUCCESS;
    }
}
