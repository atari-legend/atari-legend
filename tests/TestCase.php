<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests run before `npm run build` in CI, so there is no Vite manifest
        // for the layouts to resolve assets against.
        $this->withoutVite();
    }
}
