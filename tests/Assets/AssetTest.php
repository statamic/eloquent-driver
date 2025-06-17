<?php

namespace Tests\Assets;

use Facades\Statamic\Imaging\ImageValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Assets\Asset;
use Statamic\Eloquent\Assets\AssetModel;
use Statamic\Events\AssetCreated;
use Statamic\Events\AssetCreating;
use Statamic\Events\AssetSaved;
use Statamic\Events\AssetUploaded;
use Statamic\Facades;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    private $container;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'file']);
        Cache::clear();

        config(['filesystems.disks.test' => [
            'driver' => 'local',
            'root' => __DIR__.'/tmp',
        ]]);

        Storage::fake('test', ['url' => '/assets']);
        Storage::fake('attributes-cache');

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
    public function it_loads_from_asset_model()
    {
        $model = new AssetModel([
            'container' => 'test',
            'path' => 'test-folder/test.jpg',
            'folder' => 'test-folder',
            'basename' => 'test.jpg',
            'filename' => 'test',
            'extension' => 'jpg',
            'meta' => ['width' => 100, 'height' => 100, 'data' => []],
        ]);

        $asset = (new Asset)->fromModel($model);

        $this->assertSame($model, $asset->model());
        $this->assertSame('test-folder/test.jpg', $asset->path());
        $this->assertSame('test-folder', $asset->folder());
        $this->assertSame('test.jpg', $asset->basename());
        $this->assertSame('test', $asset->filename());
        $this->assertSame('jpg', $asset->extension());
        $this->assertSame(['width' => 100, 'height' => 100, 'data' => []], $asset->meta());
    }

    #[Test]
    public function it_finds_an_asset_and_blinks_it()
    {
        Facades\Blink::flush();

        $this->expectsDatabaseQueryCount(2); // 1 for the container, 1 for the asset meta

        $this->assertCount(0, Facades\Blink::allStartingWith('eloquent-asset-'));

        $asset = Facades\Asset::find('test::a.jpg');

        $this->assertInstanceOf(Asset::class, $asset);

        $this->assertCount(1, Facades\Blink::allStartingWith('eloquent-asset-'));

        Facades\Asset::find('test::a.jpg'); // this checks we don't perform another query
    }

    #[Test]
    public function deleting_an_asset_deletes_the_model_and_the_blink_cache()
    {
        Facades\Blink::flush();

        $this->assertCount(6, AssetModel::all());

        $asset = Facades\Asset::find('test::a.jpg');

        $this->assertCount(1, Facades\Blink::allStartingWith('eloquent-asset-'));

        $asset->delete();

        $this->assertCount(5, AssetModel::all());
        $this->assertCount(0, Facades\Blink::allStartingWith('eloquent-asset-'));
    }

    #[Test]
    public function updating_an_assets_meta_updates_the_same_model()
    {
        $asset = Facades\Asset::find('test::a.jpg');

        $this->assertCount(6, AssetModel::all());

        $model = $asset->model();

        $asset->writeMeta(['width' => 900]);
        $asset->save();

        $this->assertSame($model, $asset->model());
        $this->assertCount(6, AssetModel::all());
    }

    #[Test]
    public function making_and_saving_an_asset_creates_a_new_model()
    {
        $this->assertCount(6, AssetModel::all());

        Storage::disk('test')->put('f.jpg', '');

        $asset = Facades\Asset::make()->container('test')->path('test.jpg');
        $this->assertCount(6, AssetModel::all());

        $asset->save();

        $this->assertInstanceOf(AssetModel::class, $asset->model());
        $this->assertCount(7, AssetModel::all());
    }

    #[Test]
    public function moving_an_asset_updates_the_same_model()
    {
        $asset = Facades\Asset::find('test::a.jpg');

        $this->assertCount(6, AssetModel::all());

        $model = $asset->model();
        $modelId = $model->getKey();

        $asset->move('new-folder', 'new-name');

        $this->assertSame($model, $asset->model());
        $this->assertCount(6, AssetModel::all());

        $this->assertSame($model->path, $asset->path());
        $this->assertSame($model->folder, $asset->folder());
        $this->assertSame($model->basename, $asset->basename());
        $this->assertSame($model->filename, $asset->filename());
        $this->assertSame($model->extension, $asset->extension());
        $this->assertSame($model->meta, $asset->meta());
        $this->assertSame($modelId, $asset->model()->getKey());
    }

    #[Test]
    public function uploads_a_file()
    {
        Event::fake();
        $asset = (new \Statamic\Eloquent\Assets\Asset)->container($this->container)->path('path/to/asset.jpg')->syncOriginal();

        Facades\AssetContainer::shouldReceive('findByHandle')->with('test_container')->andReturn($this->container);
        Storage::disk('test')->assertMissing('path/to/asset.jpg');

        // This should only get called when glide processing source image on upload...
        ImageValidator::partialMock()->shouldReceive('isValidImage')->never();

        $return = $asset->upload(UploadedFile::fake()->image('asset.jpg', 13, 15));

        $this->assertEquals($asset, $return);
        Storage::disk('test')->assertExists('path/to/asset.jpg');
        $this->assertEquals('path/to/asset.jpg', $asset->path());

        $meta = $asset->meta();
        $this->assertEquals(13, $meta['width']);
        $this->assertEquals(15, $meta['height']);
        $this->assertEquals('image/jpeg', $meta['mime_type']);
        $this->assertArrayHasKey('size', $meta);
        $this->assertArrayHasKey('last_modified', $meta);
        $this->assertInstanceOf(AssetModel::class, $asset->model());

        Event::assertDispatched(AssetCreating::class, fn ($event) => $event->asset === $asset);
        Event::assertDispatched(AssetSaved::class, fn ($event) => $event->asset === $asset);
        Event::assertDispatched(AssetUploaded::class, fn ($event) => $event->asset === $asset);
        Event::assertDispatched(AssetCreated::class, fn ($event) => $event->asset === $asset);
    }

    #[Test]
    public function can_save_an_asset_made_on_the_container()
    {
        Event::fake();

        $this->assertCount(6, AssetModel::all());

        $asset = $this->container->makeAsset('a.jpg');

        $this->assertNull($asset->model());

        $asset->save();

        $this->assertNotNull($asset->model());

        Event::assertDispatched(AssetSaved::class, fn ($event) => $event->asset === $asset);

        $this->assertCount(6, AssetModel::all());
    }
}
