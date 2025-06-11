<?php

namespace Assets;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades;
use Tests\TestCase;

class AssetContainerTest extends TestCase
{
    use RefreshDatabase;

    private $container;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('test', ['url' => '/assets']);

        $this->container = tap(Facades\AssetContainer::make('test')->disk('test'))->save();

        Storage::disk('test')->put('a.jpg', '');
        Facades\Asset::make()->container('test')->path('a.jpg')->save();

        Storage::disk('test')->put('b.txt', '');
        Facades\Asset::make()->container('test')->path('b.txt')->save();

        Storage::disk('test')->put('c.txt', '');
        Facades\Asset::make()->container('test')->path('c.txt')->save();

        Storage::disk('test')->put('d.jpg', '');
        Facades\Asset::make()->container('test')->path('d.jpg')->save();

        Storage::disk('test')->put('e.jpg', '');
        Facades\Asset::make()->container('test')->path('e.jpg')->save();

        Storage::disk('test')->put('f.jpg', '');
        Facades\Asset::make()->container('test')->path('f.jpg')->save();
    }

    #[Test]
    public function calling_folders_uses_eloquent_asset_container_contents()
    {
        $this->expectsDatabaseQueryCount(1);

        $queryExecuted = false;
        \DB::listen(function (QueryExecuted $query) use (&$queryExecuted) {
            $queryExecuted = str_contains($query->sql, 'select distinct "folder" from "assets_meta"');
        });

        $this->container->folders();

        $this->assertTrue($queryExecuted);
    }

    #[Test]
    public function creating_a_folder_adds_it_to_the_folder_cache()
    {
        $this->assertCount(1, $this->container->folders());
        $this->assertCount(1, Cache::get('asset-folder-contents-'.$this->container->handle()));

        $this->container->assetFolder('foo')->save();

        $this->assertCount(2, $this->container->folders());
        $this->assertCount(2, Cache::get('asset-folder-contents-'.$this->container->handle()));
        $this->assertSame(['/', 'foo'], $this->container->folders()->all());

    }
}
