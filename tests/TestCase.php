<?php

namespace Ajaxray\ServerSync\Tests;

use Ajaxray\ServerSync\ServerSyncServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{

    public function setUp(): void
    {
        parent::setUp();
        // additional setup
    }

    protected function getPackageProviders($app)
    {
        return [
            ServerSyncServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('server-sync.production', [ 
            'host' => 'localhost',
            'user' => 'root',
            'path' => '/var/www/html/laravel',
        ]); 
    }
}
