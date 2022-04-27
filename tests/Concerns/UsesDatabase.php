<?php

namespace Tests\Concerns;

use Generator;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Events\Dispatcher;

trait UsesDatabase
{
    /**
     * Create a basic IOC container and the infrastructure necessary to run Eloquent.
     */
    protected function bootstrapApplication()
    {
        $this->createDatabase($this->createContainer());
    }

    /**
     * Create a simple IOC container.
     */
    protected function createContainer(): Container
    {
        // Set the static instance to null to have a "fresh" container for each test.
        Container::setInstance();

        return Container::getInstance();
    }

    /**
     * Create a non persistant database in memory.
     */
    protected function createDatabase(Container $container)
    {
        $database = new Manager();
        $database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $database->setEventDispatcher(new Dispatcher($container));
        $database->setAsGlobal();
        $database->bootEloquent();

        Factory::useNamespace('Tests\\Factories\\');

        // We need to trick the Factory class into thinking that Tests\Fakes is the root namespace of the application.
        $container->bind(Application::class, fn() => new class {
            public function getNamespace(): string
            {
                return 'Tests\\Fakes\\';
            }
        });

        // Make it possible to use faker in factories.
        $container->singleton(\Faker\Generator::class, function() {
            return \Faker\Factory::create();
        });
    }

    /**
     * Run the migrations to create the tables used by the tests.
     */
    protected function createTables()
    {
        foreach ($this->getMigrations() as $migration) {
            $migration->up();
        }
    }

    /**
     * Run the migration rollback.
     */
    protected function dropTables()
    {
        foreach ($this->getMigrations(true) as $migration) {
            $migration->down();
        }
    }

    /**
     * Get all the available migrations.
     */
    protected function getMigrations(bool $reverse = false): Generator
    {
        $migrations = glob(__DIR__ . '/../migrations/*.php');

        if ($reverse) {
            array_reverse($migrations);
        }

        foreach ($migrations as $migration) {
            yield require $migration;
        }
    }
}