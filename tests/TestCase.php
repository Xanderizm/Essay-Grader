<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Pengaman: menolak berjalan jika bukan database uji, agar data asli tidak terhapus
    public function createApplication()
    {
        $app = parent::createApplication();

        $default = $app['config']->get('database.default');
        $db      = (string) $app['config']->get("database.connections.$default.database");

        if (! str_ends_with($db, '_test') && $db !== ':memory:') {
            throw new \RuntimeException(
                "Pengaman: pengujian mencoba memakai database '$db'. Atur phpunit.xml agar memakai database berakhiran _test."
            );
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // test tidak butuh public/build/manifest.json
    }
}
