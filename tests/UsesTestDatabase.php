<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Test DB isolation for this project's pooled Postgres connection.
 *
 * Laravel's RefreshDatabase / DatabaseTransactions both wrap each test in a
 * transaction, and on this connection that transaction is already aborted by
 * the time the test body runs -- every write fails with SQLSTATE 25P02. The
 * migrations document the same conflict ("pooled pgsql aborts multi-statement
 * transactions"), which is why they set public $withinTransaction = false.
 *
 * So: migrate once per process, TRUNCATE between tests. Same isolation
 * guarantee (no state leaks between tests), no transaction wrapper.
 *
 * Runs against phpunit.xml's DB_DATABASE, never the app schema.
 *
 * Note: a class that defines its own setUp() shadows this trait's hook, so
 * call parent::setUp() or use defineEnvironment() for per-test config.
 */
trait UsesTestDatabase
{
    protected static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! static::$migrated) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            static::$migrated = true;

            return;
        }

        $tables = array_map(
            fn ($t) => '"'.$t->tablename.'"',
            DB::select('select tablename from pg_tables where schemaname = current_schema()'),
        );

        if ($tables !== []) {
            DB::statement('TRUNCATE TABLE '.implode(', ', $tables).' RESTART IDENTITY CASCADE');
        }
    }
}