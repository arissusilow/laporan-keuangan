<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $environment = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? getenv('APP_ENV');
        $connection = $_ENV['DB_CONNECTION'] ?? $_SERVER['DB_CONNECTION'] ?? getenv('DB_CONNECTION');
        $database = $_ENV['DB_DATABASE'] ?? $_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE');
        $databaseUrl = $_ENV['DB_URL'] ?? $_SERVER['DB_URL'] ?? getenv('DB_URL');

        if ($environment !== 'testing'
            || $connection !== 'sqlite'
            || $database !== ':memory:'
            || ! in_array($databaseUrl, [false, null, ''], true)) {
            throw new \RuntimeException('Test dibatalkan: hanya environment testing dengan SQLite :memory: yang diizinkan.');
        }

        parent::setUp();

        if (! app()->environment('testing')
            || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Test dibatalkan: hanya database SQLite :memory: yang diizinkan.');
        }
    }
}
