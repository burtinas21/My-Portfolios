<?php

namespace Tests;

use Illuminate\Support\Facades\Config;
use RuntimeException;

/**
 * Safety guard to prevent tests from running against production database.
 * 
 * This trait ensures tests only run on SQLite in-memory database,
 * protecting your real PostgreSQL data from accidental deletion.
 */
trait DatabaseSafetyGuard
{
    /**
     * Check database connection before running tests.
     * 
     * @throws RuntimeException if not using SQLite memory database
     */
    protected function setUpDatabaseSafetyGuard(): void
    {
        // Get the actual database connection from environment
        // This works even if config is cached because we read from $_ENV
        $defaultConnection = $_ENV['DB_CONNECTION'] ?? Config::get('database.default');
        $database = $_ENV['DB_DATABASE'] ?? Config::get('database.connections.' . $defaultConnection . '.database');

        // Only allow SQLite in-memory database for tests
        if ($defaultConnection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "SAFETY GUARD: Tests can only run on SQLite in-memory database!\n" .
                "Current connection: {$defaultConnection}\n" .
                "Current database: {$database}\n\n" .
                "Your production data is protected. Tests have been blocked.\n" .
                "Check phpunit.xml to ensure DB_CONNECTION=sqlite and DB_DATABASE=:memory:"
            );
        }
    }

    /**
     * Setup before each test.
     * Called automatically by PHPUnit.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Verify safe database connection
        $this->setUpDatabaseSafetyGuard();
    }
}
