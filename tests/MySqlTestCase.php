<?php

namespace Tests;

abstract class MySqlTestCase extends TestCase
{
    public function createApplication()
    {
        // Do not inherit the application's DB_* values from .env. The test
        // database must be chosen explicitly, before any migrations can run.
        $database = getenv('MYSQL_TEST_DATABASE');
        if (! $database || ! preg_match('/(?:_test|_testing)$/', $database)) {
            throw new \RuntimeException('Set MYSQL_TEST_DATABASE to a disposable database ending in _test or _testing.');
        }

        $settings = [
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => $database,
            'DB_HOST' => getenv('MYSQL_TEST_HOST') ?: '127.0.0.1',
            'DB_PORT' => getenv('MYSQL_TEST_PORT') ?: '3306',
            'DB_USERNAME' => getenv('MYSQL_TEST_USERNAME') ?: 'root',
            'DB_PASSWORD' => getenv('MYSQL_TEST_PASSWORD') ?: '',
            'DB_SOCKET' => getenv('MYSQL_TEST_SOCKET') ?: '',
            'DATABASE_URL' => '',
        ];

        foreach ($settings as $key => $value) {
            $_ENV[$key] = $_SERVER[$key] = $value;
            putenv($key.'='.$value);
        }

        $app = parent::createApplication();
        if ($app->configurationIsCached()) {
            throw new \RuntimeException('Clear the config cache before running database tests.');
        }

        return $app;
    }
}
