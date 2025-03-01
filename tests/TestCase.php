<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Tests\Concerns\CreatesApplication;
use Tests\Concerns\SetupTestDatabase;
use Tests\Concerns\TestContext;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication,
        DatabaseMigrations,
        SetupTestDatabase;

    /**
     * setUp
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Setup Test DB
        $this->createTestDatabaseAndSetConnection();

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp', [
            'transport' => 'smtp',
            'host' => '127.0.0.1',
            'port' => '1025',
            'encryption' => null,
            'username' => null,
            'password' => null,
            'timeout' => null,
        ]);
    }

    /**
     * tearDown
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeTestDatabase();
    }

    /**
     * Get Original Response
     */
    protected function original($response)
    {
        $original = $response->original;

        return ($original instanceof JsonResource) ? $original->resource : $original;
    }

    /**
     * Decode Response
     */
    protected function content($response, $asArray = true)
    {
        return json_decode($response->content(), (bool) $asArray) ?? $response->content();
    }

    /**
     * Create context (User/ Tenant) for Testing
     *
     * @example $context = $this->makeUserAndTenant()
     *	->user(fn($f) => $f->unverified())
     *	->authenticate()
     *	->role('admin')
     *	->tenant(fn($f) => $f->state(['name' => 'Hello']))
     *	->create()
     */
    protected function makeUserAndTenant(): TestContext
    {
        return new TestContext($this);
    }
}
