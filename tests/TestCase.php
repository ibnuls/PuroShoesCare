<?php

namespace Tests;

abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    protected $seed = true;

    public function createApplication()
    {
        $app = parent::createApplication();
        $db = $app['config']->get('database.connections.pgsql.database');
        if ($app['config']->get('database.default') !== 'pgsql' || ! str_ends_with($db, '_testing')) {
            throw new \RuntimeException('TEST DIBATALKAN: wajib PostgreSQL dengan nama database berakhiran _testing.');
        }

        return $app;
    }
}
