<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * A cached bootstrap/cache/config.php silently overrides the sqlite settings
     * in phpunit.xml, which points the whole suite — RefreshDatabase included —
     * at the live production database. Refuse to run rather than risk it.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || ! in_array($database, [':memory:', 'testing'], true)) {
            throw new RuntimeException(
                "Refusing to run tests against '{$connection}' database '{$database}'. "
                . 'This is usually a cached config: run `php artisan config:clear` first '
                . '(and `php artisan config:cache` again afterwards).'
            );
        }
    }
}
