<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

trait SetupTestDatabase
{
    protected $usingLiveDb = false;

    protected $origConnectionName;

    protected $testConnectionName;

    protected $testDatabase;

    /**
     * Create Test Database and swap Connect
     */
    private function createTestDatabaseAndSetConnection()
    {
        if (env('DB_CONNECTION') !== 'mysql') {
            return;
        }

        // Setup
        $this->origConnectionName = 'mysql'; // Mysql
        $this->testConnectionName = 'mysql_testing';

        // Copy the config to testing
        $orginalConnectionConfigPath = 'database.connections.mysql';
        $testingConnectionConfigPath = 'database.connections.mysql_testing';
        config([$testingConnectionConfigPath => config($orginalConnectionConfigPath)]);

        // Set and Create Testing DB
        $this->testDatabase = config("{$testingConnectionConfigPath}.database").'_testing';
        DB::connection($this->origConnectionName)->statement('CREATE DATABASE IF NOT EXISTS '.$this->testDatabase);
        config(["{$testingConnectionConfigPath}.database" => $this->testDatabase]);

        DB::purge($this->origConnectionName);
        DB::setDefaultConnection($this->testConnectionName);

        // Migrate
        Artisan::call('migrate:fresh');
    }

    /**
     * Delete Test Database
     */
    private function removeTestDatabase()
    {
        if (env('DB_CONNECTION') !== 'mysql') {
            return;
        }

        if ($this->testDatabase) {
            DB::connection($this->testConnectionName)->statement('DROP DATABASE IF EXISTS '.$this->testDatabase);
            if (! $this->usingLiveDb) {
                DB::connection()->setPdo(null);
            }
        }
    }

    protected function useLiveDB()
    {
        DB::purge($this->testConnectionName);
        DB::setDefaultConnection($this->origConnectionName);
        $this->usingLiveDb = true;
    }
}
