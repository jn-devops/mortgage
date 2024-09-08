<?php

namespace Homeful\Mortgage;

use Spatie\LaravelPackageTools\PackageServiceProvider;
use Homeful\Mortgage\Commands\MortgageCommand;
use Spatie\LaravelPackageTools\Package;

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
            ->hasConfigFile(['mortgage', 'payment', 'property'])
            ->hasCommand(MortgageCommand::class);
    }
}
