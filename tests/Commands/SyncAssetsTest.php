<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Assets\AssetContainerModel;
use Statamic\Eloquent\Assets\AssetModel;
use Tests\TestCase;

class SyncAssetsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.disks.test' => [
            'driver' => 'local',
            'root' => __DIR__.'/tmp',
        ]]);

        AssetContainerModel::create([
            'handle' => 'test',
            'title' => 'Test',
            'disk' => 'test',
            'settings' => [],
        ]);
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory(__DIR__.'/tmp');
        parent::tearDown();
    }

    #[Test]
    public function it_adds_new_root_level_file_to_the_database()
    {
        Storage::disk('test')->put('hero.webp', 'fake image data');

        $this->artisan('statamic:eloquent:sync-assets')->assertExitCode(0);

        $this->assertDatabaseHas('assets_meta', [
            'container' => 'test',
            'path' => 'hero.webp',
        ]);
    }

    #[Test]
    public function it_adds_new_nested_folder_file_to_the_database()
    {
        Storage::disk('test')->put('img/landing-page/hero.webp', 'fake image data');

        $this->artisan('statamic:eloquent:sync-assets')->assertExitCode(0);

        $this->assertDatabaseHas('assets_meta', [
            'container' => 'test',
            'path' => 'img/landing-page/hero.webp',
        ]);
    }

    #[Test]
    public function it_does_not_delete_existing_nested_folder_assets_from_the_database()
    {
        Storage::disk('test')->put('img/landing-page/hero.webp', 'fake image data');

        AssetModel::create([
            'container' => 'test',
            'folder' => 'img/landing-page',
            'basename' => 'hero.webp',
            'filename' => 'hero',
            'extension' => 'webp',
            'path' => 'img/landing-page/hero.webp',
            'meta' => [],
        ]);

        $this->artisan('statamic:eloquent:sync-assets')->assertExitCode(0);

        $this->assertDatabaseHas('assets_meta', [
            'container' => 'test',
            'path' => 'img/landing-page/hero.webp',
        ]);
    }

    #[Test]
    public function it_deletes_stale_nested_folder_assets_from_the_database()
    {
        // folder exists on disk with one current file and one that was deleted
        Storage::disk('test')->put('img/landing-page/current.png', 'fake image data');

        AssetModel::create([
            'container' => 'test',
            'folder' => 'img/landing-page',
            'basename' => 'current.png',
            'filename' => 'current',
            'extension' => 'png',
            'path' => 'img/landing-page/current.png',
            'meta' => [],
        ]);

        AssetModel::create([
            'container' => 'test',
            'folder' => 'img/landing-page',
            'basename' => 'old-file.svg',
            'filename' => 'old-file',
            'extension' => 'svg',
            'path' => 'img/landing-page/old-file.svg',
            'meta' => [],
        ]);

        $this->artisan('statamic:eloquent:sync-assets')->assertExitCode(0);

        $this->assertDatabaseMissing('assets_meta', [
            'container' => 'test',
            'path' => 'img/landing-page/old-file.svg',
        ]);

        $this->assertDatabaseHas('assets_meta', [
            'container' => 'test',
            'path' => 'img/landing-page/current.png',
        ]);
    }

    #[Test]
    public function it_deletes_stale_root_level_assets_from_the_database()
    {
        Storage::disk('test')->put('current.png', 'fake image data');

        AssetModel::create([
            'container' => 'test',
            'folder' => '/',
            'basename' => 'current.png',
            'filename' => 'current',
            'extension' => 'png',
            'path' => 'current.png',
            'meta' => [],
        ]);

        AssetModel::create([
            'container' => 'test',
            'folder' => '/',
            'basename' => 'old-file.svg',
            'filename' => 'old-file',
            'extension' => 'svg',
            'path' => 'old-file.svg',
            'meta' => [],
        ]);

        $this->artisan('statamic:eloquent:sync-assets')->assertExitCode(0);

        $this->assertDatabaseMissing('assets_meta', [
            'container' => 'test',
            'path' => 'old-file.svg',
        ]);

        $this->assertDatabaseHas('assets_meta', [
            'container' => 'test',
            'path' => 'current.png',
        ]);
    }
}
