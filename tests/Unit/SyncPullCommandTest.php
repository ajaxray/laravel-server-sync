<?php

namespace Tests\Unit;

use Ajaxray\ServerSync\Commands\SyncPullCommand;
use Illuminate\Support\Facades\Config;
use Ajaxray\ServerSync\Tests\TestCase;

class SyncPullCommandTest extends TestCase
{
    public function testProductionHostIsFetchedByDefault()
    {
        $host = Config::get('server-sync.production.host');
        $user = Config::get('server-sync.production.user');
        // Mock the command
        $this->artisan(SyncPullCommand::class, [            
            '--skip-db' => true,
            '--skip-files' => true,
        ])
            ->expectsOutputToContain("Pulling from {$host} as {$user}")            
            ->assertExitCode(0)
            ;
    }

    public function testRemoteOptionAreFetchedCorrectly()
    {
        // Set up the configuration for the test
        Config::set('server-sync.staging.host', 'staging.example.com');
        Config::set('server-sync.staging.user', 'staging_user');
        Config::set('server-sync.staging.path', '/path/to/staging');

        // Mock the command
        $this->artisan(SyncPullCommand::class, [
            '--remote' => 'staging',
            '--skip-db' => true,
            '--skip-files' => true,
        ])
            ->expectsOutputToContain('Pulling from staging.example.com as staging_user')            
            ->assertExitCode(0)
            ;
    }

    public function testCommandOptionsOverwritesConfigValues()
    {
        // Set up default config values
        Config::set('server-sync.production.host', 'default.example.com');
        Config::set('server-sync.production.user', 'default_user'); 
        Config::set('server-sync.production.path', '/default/path');

        // Mock the command with overriding options
        $this->artisan(SyncPullCommand::class, [
            '--host' => 'custom.example.com',
            '--user' => 'custom_user',
            '--path' => '/custom/path',
            '--skip-db' => true,
            '--skip-files' => true,
        ])
            ->expectsOutputToContain('Pulling from custom.example.com as custom_user')
            ->assertExitCode(0)
            ;
    }
}