<?php

namespace Homeful\Mortgage;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Homeful\Mortgage\Commands\MortgageCommand;

class MortgageServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('mortgage')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_mortgage_table')
            ->hasCommand(MortgageCommand::class);
    }
}
