<?php

namespace Homeful\Mortgage\Tests;

use Homeful\Mortgage\MortgageServiceProvider;
use Homeful\Mortgage\Providers\EventServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Homeful\\Mortgage\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            MortgageServiceProvider::class,
            EventServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('data.validation_strategy', 'always');
        config()->set('data.max_transformation_depth', 5);
        config()->set('data.throw_when_max_transformation_depth_reached', 5);
        /*
        $migration = include __DIR__.'/../database/migrations/create_mortgage_table.php.stub';
        $migration->up();
        */
    }
}
