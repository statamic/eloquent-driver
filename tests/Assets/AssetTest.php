<?php

namespace Tests\Assets;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    private $container;

    public function setUp(): void
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

        $asset->save();

        $this->assertFalse(Facades\Blink::has("eloquent-asset-{$asset->id()}"));
    }

    #[Test]
    public function not_referencing_by_id_gives_a_container_and_path_id()
    {
        $asset = Facades\Asset::find('test::f.jpg');

        $this->assertNotSame($asset->id(), $asset->model()->getKey());
        $this->assertStringContainsString("::", $asset->id());
    }

    #[Test]
    #[DefineEnvironment('setUseModelKeysConfig')]
    public function referencing_by_id_gives_a_model_id()
    {
        $asset = Facades\Asset::find('test::f.jpg');

        $this->assertSame($asset->id(), $asset->model()->getKey());
    }

    #[Test]
    #[DefineEnvironment('setUseModelKeysConfig')]
    public function an_error_is_thrown_when_getting_id_before_asset_is_saved()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('ID is not available until asset is saved');

        Storage::disk('test')->put('new.jpg', '');
        Facades\Asset::make()->container('test')->path('new.jpg')->id();
    }

    protected function setUseModelKeysConfig($app)
    {
        $app['config']->set('statamic.eloquent-driver.assets.use_model_keys_for_ids', true);
    }
}
