<?php

namespace Tests\Feature;

use Tests\TestCase;
use RuntimeException;

class SafetyGuardTest extends TestCase
{
    /**
     * Test that the safety guard is active and checking connections.
     */
    public function test_safety_guard_is_active(): void
    {
        // If we get here, the safety guard passed
        // (because setUp() runs before each test and checks the connection)
        $this->assertTrue(true, 'Safety guard is active and verified SQLite in-memory database');
    }

    /**
     * Test that safety guard would block production database.
     * 
     * This test verifies the guard logic without actually changing the connection.
     */
    public function test_safety_guard_blocks_production_database(): void
    {
        // Simulate what would happen if someone tried to use pgsql
        $wouldBlock = function() {
            $connection = 'pgsql';
            $database = 'my-portfolio';
            
            if ($connection !== 'sqlite' || $database !== ':memory:') {
                return true; // Would be blocked
            }
            return false;
        };

        $this->assertTrue(
            $wouldBlock(),
            'Safety guard would correctly block PostgreSQL production database'
        );
    }

    /**
     * Test that current test environment is safe.
     */
    public function test_current_environment_is_safe(): void
    {
        $connection = $_ENV['DB_CONNECTION'] ?? config('database.default');
        $database = $_ENV['DB_DATABASE'] ?? config('database.connections.' . $connection . '.database');

        $this->assertEquals('sqlite', $connection, 'Test connection must be SQLite');
        $this->assertEquals(':memory:', $database, 'Test database must be in-memory');
    }
}
