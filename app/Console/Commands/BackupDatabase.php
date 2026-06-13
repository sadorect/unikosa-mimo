<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'Dump the PostgreSQL database and upload the backup to Cloudflare R2';

    public function handle(): int
    {
        $filename = 'backups/db-' . now()->format('Y-m-d_H-i-s') . '.sql.gz';
        $tempPath = sys_get_temp_dir() . '/' . basename($filename);

        $host     = config('database.connections.pgsql.host');
        $port     = config('database.connections.pgsql.port', 5432);
        $dbName   = config('database.connections.pgsql.database');
        $username = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');

        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s %s | gzip > %s',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($dbName),
            escapeshellarg($tempPath),
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->error('pg_dump failed.');
            return self::FAILURE;
        }

        $stream = fopen($tempPath, 'r');
        Storage::disk('r2')->put($filename, $stream);
        fclose($stream);
        @unlink($tempPath);

        $this->info("Database backup uploaded to R2: {$filename}");
        $this->pruneOldBackups();

        return self::SUCCESS;
    }

    private function pruneOldBackups(): void
    {
        $files = collect(Storage::disk('r2')->files('backups'))
            ->sortDesc()
            ->values();

        // Keep last 14 backups
        $files->slice(14)->each(fn ($f) => Storage::disk('r2')->delete($f));
    }
}
