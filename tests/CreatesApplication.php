<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        if ($app->configurationIsCached()) {
            throw new \RuntimeException('Run php artisan config:clear before tests so the isolated database settings are used.');
        }

        $app->make(Kernel::class)->bootstrap();

        if (! $this instanceof MySqlTestCase) {
            $connection = $app['db']->connection();
            if ($connection->getDriverName() !== 'sqlite' || $connection->getDatabaseName() !== ':memory:') {
                throw new \RuntimeException('The default test suite requires SQLite :memory:; use phpunit.mysql.xml for isolated MySQL tests.');
            }
        }

        return $app;
    }
}
