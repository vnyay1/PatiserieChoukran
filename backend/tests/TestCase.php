<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Factures, rapports et images générés par les tests : jamais dans storage/app
        Storage::fake('local');
        Storage::fake('public');
    }
}
