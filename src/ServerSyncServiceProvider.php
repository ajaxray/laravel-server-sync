<?php

namespace Ajaxray\ServerSync;

use Ajaxray\ServerSync\Commands\SyncPullCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ServerSyncServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-server-sync')
            ->hasConfigFile()
            ->hasCommand(SyncPullCommand::class);
    }
} 