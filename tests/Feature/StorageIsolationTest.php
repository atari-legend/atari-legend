<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageIsolationTest extends TestCase
{
    public function test_storage_path_is_isolated_to_tmp(): void
    {
        $this->assertSame('/tmp/atari-legend-storage', $this->app->storagePath());
    }

    public function test_faked_storage_creates_files_under_isolated_storage_path(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('test-file.txt', 'test content');

        Storage::disk('public')->assertExists('test-file.txt');

        $path = Storage::disk('public')->path('test-file.txt');
        $this->assertStringStartsWith('/tmp/atari-legend-storage', $path);
        $this->assertFileExists($path);
    }
}
