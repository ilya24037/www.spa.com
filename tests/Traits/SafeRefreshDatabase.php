<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * SafeRefreshDatabase trait for PHP 8.4 compatibility
 *
 * Handles SQLite transaction issues with PHP 8.4 where
 * nested transactions cause "cannot start a transaction within a transaction" errors.
 */
trait SafeRefreshDatabase
{
    use RefreshDatabase {
        RefreshDatabase::beginDatabaseTransaction as parentBeginDatabaseTransaction;
        RefreshDatabase::refreshDatabase as parentRefreshDatabase;
    }

    /**
     * Begin a database transaction on the testing database.
     *
     * @return void
     */
    public function beginDatabaseTransaction()
    {
        $database = $this->app->make('db');

        foreach ($this->connectionsToTransact() as $name) {
            $connection = $database->connection($name);
            $dispatcher = $connection->getEventDispatcher();

            $connection->unsetEventDispatcher();

            // Check if we're using SQLite and PHP 8.4+
            if ($this->isSqliteWithPhp84($connection)) {
                $this->handleSqliteTransaction($connection);
            } else {
                $connection->beginTransaction();
            }

            $connection->setEventDispatcher($dispatcher);
        }

        $this->beforeApplicationDestroyed(function () use ($database) {
            foreach ($this->connectionsToTransact() as $name) {
                $connection = $database->connection($name);
                $dispatcher = $connection->getEventDispatcher();

                $connection->unsetEventDispatcher();
                $connection->rollBack();
                $connection->setEventDispatcher($dispatcher);
                $connection->disconnect();
            }
        });
    }

    /**
     * Check if we're using SQLite with PHP 8.4+
     *
     * @param \Illuminate\Database\Connection $connection
     * @return bool
     */
    protected function isSqliteWithPhp84($connection): bool
    {
        return $connection->getDriverName() === 'sqlite'
            && version_compare(PHP_VERSION, '8.4.0', '>=');
    }

    /**
     * Handle SQLite transaction for PHP 8.4
     *
     * @param \Illuminate\Database\Connection $connection
     * @return void
     */
    protected function handleSqliteTransaction($connection): void
    {
        $pdo = $connection->getPdo();

        // Check if transaction is already active
        if (!$pdo->inTransaction()) {
            // Use IMMEDIATE mode to avoid SQLITE_BUSY errors
            $connection->getPdo()->exec('BEGIN IMMEDIATE TRANSACTION');
        }
    }

    /**
     * Refresh the in-memory database.
     *
     * @return void
     */
    public function refreshDatabase()
    {
        $this->parentRefreshDatabase();
    }
}