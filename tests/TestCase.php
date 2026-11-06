<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the installed marker exists so CheckInstalled middleware
        // doesn't redirect admin routes to the installer during tests.
        if (!file_exists(storage_path('installed'))) {
            file_put_contents(storage_path('installed'), json_encode([
                'installed_at' => now()->toDateTimeString(),
                'version' => '1.0.0',
            ]));
        }
    }
}
