<?php

namespace Homeful\Mortgage;

use Homeful\Mortgage\Commands\MortgageCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
            ->hasConfigFile(['mortgage', 'payment'])
            ->hasViews()
            ->hasMigration('create_mortgage_table')
            ->hasCommand(MortgageCommand::class);
    }
}
