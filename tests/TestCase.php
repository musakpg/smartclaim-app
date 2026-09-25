<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite') {
            $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
            $pdo->sqliteCreateFunction('MONTH', function ($date) {
                return $date ? (int) date('m', strtotime($date)) : null;
            }, 1);
            $pdo->sqliteCreateFunction('YEAR', function ($date) {
                return $date ? (int) date('Y', strtotime($date)) : null;
            }, 1);
        }
    }
}
