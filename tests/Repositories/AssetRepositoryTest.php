<?php

namespace Tests\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades;
use Tests\TestCase;

class AssetRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.disks.test' => [
            'driver' => 'local',
            'root' => __DIR__.'/tmp',
        ]]);

        Storage::fake('test', ['url' => '/assets']);

        tap(Facades\AssetContainer::make('test')->disk('test'))->save();
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory(__DIR__.'/tmp');

        parent::tearDown();
    }

    #[Test]
    public function it_finds_an_asset_by_url()
    {
        Storage::disk('test')->put('a.jpg', '');
        $asset = tap(Facades\Asset::make()->container('test')->path('a.jpg'))->save();

        $this->assertEquals($asset->id(), Facades\Asset::findByUrl($asset->url())->id());
    }

    /**
     * The URL is written out encoded rather than taken from $asset->url(), because
     * whether that method encodes has varied across Statamic versions. What matters
     * here is that an encoded URL resolves, however it was produced.
     */
    #[Test]
    public function it_finds_an_asset_by_url_when_the_path_is_percent_encoded()
    {
        Storage::disk('test')->put('my file (1) @ café.jpg', '');
        $asset = tap(Facades\Asset::make()->container('test')->path('my file (1) @ café.jpg'))->save();

        $this->assertEquals(
            $asset->id(),
            Facades\Asset::findByUrl('/assets/my%20file%20%281%29%20%40%20caf%C3%A9.jpg')->id()
        );
    }
}
