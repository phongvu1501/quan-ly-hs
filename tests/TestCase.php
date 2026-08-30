<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Fail before RefreshDatabase can touch anything outside the isolated test database.
     *
     * @return array<class-string, int>
     */
    protected function setUpTraits(): array
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if (! app()->environment('testing') || ! str_contains($database, '_testing')) {
            throw new \RuntimeException(
                "Unsafe test database [{$database}]. PHPUnit requires a database containing _testing."
            );
        }

        return parent::setUpTraits();
    }
}
