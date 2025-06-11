<?php

namespace Tests\Assets;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Assets\Asset;
use Statamic\Facades;
use Tests\TestCase;

class AssetTest extends TestCase
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
    public function saving_an_asset_clears_the_eloquent_blink_cache()
    {
        $asset = Facades\Asset::find('test::f.jpg');

        $this->assertTrue(Facades\Blink::has("eloquent-asset-{$asset->id()}"));
        $this->assertInstanceOf(Asset::class, Facades\Blink::get("eloquent-asset-{$asset->id()}"));

        $asset->save();

        $this->assertFalse(Facades\Blink::has("eloquent-asset-{$asset->id()}"));
    }

    #[Test]
    public function making_an_asset_without_saving_doesnt_make_a_meta_exists_blink_cache_entry()
    {
        Facades\Blink::flush();

        $this->assertCount(0, Facades\Blink::allStartingWith('eloquent-asset-meta-exists-'));

        Storage::disk('test')->put('test.jpg', '');
        $asset = Facades\Asset::make()->container('test')->path('test.jpg');

        $this->assertCount(0, Facades\Blink::allStartingWith('eloquent-asset-meta-exists-'));

        $asset->save();

        $this->assertCount(1, Facades\Blink::allStartingWith('eloquent-asset-meta-exists-'));
    }
}
