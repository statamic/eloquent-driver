<?php

namespace Tests\Commands;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Contracts\Assets\AssetContainer as AssetContainerContract;
use Statamic\Contracts\Assets\AssetContainerRepository as AssetContainerRepositoryContract;
use Statamic\Contracts\Assets\AssetRepository as AssetRepositoryContract;
use Statamic\Eloquent\Assets\AssetContainerModel;
use Statamic\Eloquent\Assets\AssetModel;
use Statamic\Facades\AssetContainer;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportAssetsTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.disks.test' => [
            'driver' => 'local',
            'root' => __DIR__.'/tmp',
        ]]);

        Facade::clearResolvedInstance(AssetContainerRepositoryContract::class);
        Facade::clearResolvedInstance(AssetRepositoryContract::class);

        app()->bind(AssetContainerContract::class, \Statamic\Assets\AssetContainer::class);
        app()->bind(AssetContract::class, \Statamic\Assets\Asset::class);
        app()->bind(AssetContainerRepositoryContract::class, \Statamic\Stache\Repositories\AssetContainerRepository::class);
        app()->bind(AssetRepositoryContract::class, \Statamic\Assets\AssetRepository::class);
        app()->bind(\Statamic\Eloquent\Assets\AssetQueryBuilder::class, \Statamic\Assets\QueryBuilder::class);
        app()->bind(\Statamic\Assets\AssetContainerContents::class, \Statamic\Assets\AssetContainerContents::class);
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory(__DIR__.'/tmp');

        parent::tearDown();
    }

    #[Test]
    public function it_gets_all_meta_files_by_default()
    {
        $container = tap(AssetContainer::make('test')->disk('test'))->save();
        Storage::disk('test')->put('.meta/a.txt.yaml',
            "data:
                    title: 'File A'
                    size: 123
                    last_modified: 0000000000"
        );
        $this->assertEquals([
            '.meta/a.txt.yaml',
        ], $container->metaFiles()->all());
    }

    #[Test]
    public function it_imports_asset_containers_and_assets()
    {
        $container = tap(AssetContainer::make('test')->disk('test'))->save();
        $container->makeAsset('one.txt')->upload(UploadedFile::fake()->create('one.txt'));
        $container->makeAsset('two.jpg')->upload(UploadedFile::fake()->image('two.jpg'));
        $container->makeAsset('subdirectory/other.txt')->upload(UploadedFile::fake()->create('other.txt'));

        $this->assertCount(0, AssetContainerModel::all());
        $this->assertCount(0, AssetModel::all());

        $this->artisan('statamic:eloquent:import-assets')
            ->expectsQuestion('Do you want to import asset containers?', true)
            ->expectsOutputToContain('Assets containers imported sucessfully.')
            ->expectsQuestion('Do you want to import assets?', true)
            ->expectsOutputToContain('Assets imported sucessfully.')
            ->assertExitCode(0);

        $this->assertCount(1, AssetContainerModel::all());
        $this->assertCount(3, AssetModel::all());

        $this->assertDatabaseHas('asset_containers', ['handle' => 'test', 'disk' => 'test']);
        $this->assertDatabaseHas('assets_meta', ['container' => 'test', 'path' => 'one.txt']);
        $this->assertDatabaseHas('assets_meta', ['container' => 'test', 'path' => 'two.jpg']);
        $this->assertDatabaseHas('assets_meta', ['container' => 'test', 'path' => 'subdirectory/other.txt']);
    }

    #[Test]
    public function it_imports_asset_containers_and_assets_with_force_argument()
    {
        $container = tap(AssetContainer::make('test')->disk('test'))->save();
        $container->makeAsset('one.txt')->upload(UploadedFile::fake()->create('one.txt'));
        $container->makeAsset('two.jpg')->upload(UploadedFile::fake()->image('two.jpg'));
        $container->makeAsset('subdirectory/other.txt')->upload(UploadedFile::fake()->create('other.txt'));

        $this->assertCount(0, AssetContainerModel::all());
        $this->assertCount(0, AssetModel::all());

        $this->artisan('statamic:eloquent:import-assets', ['--force' => true])
            ->expectsOutputToContain('Assets containers imported sucessfully.')
            ->expectsOutputToContain('Assets imported sucessfully.')
            ->assertExitCode(0);

        $this->assertCount(1, AssetContainerModel::all());
        $this->assertCount(3, AssetModel::all());

        $this->assertDatabaseHas('asset_containers', ['handle' => 'test', 'disk' => 'test']);
        $this->assertDatabaseHas('assets_meta', ['container' => 'test', 'path' => 'one.txt']);
        $this->assertDatabaseHas('assets_meta', ['container' => 'test', 'path' => 'two.jpg']);
        $this->assertDatabaseHas('assets_meta', ['container' => 'test', 'path' => 'subdirectory/other.txt']);
    }

    #[Test]
    public function it_imports_asset_containers_with_only_asset_containers_argument()
    {
        $container = tap(AssetContainer::make('test')->disk('test'))->save();
        $container->makeAsset('one.txt')->upload(UploadedFile::fake()->create('one.txt'));
        $container->makeAsset('two.jpg')->upload(UploadedFile::fake()->image('two.jpg'));
        $container->makeAsset('subdirectory/other.txt')->upload(UploadedFile::fake()->create('other.txt'));

        $this->assertCount(0, AssetContainerModel::all());
        $this->assertCount(0, AssetModel::all());

        $this->artisan('statamic:eloquent:import-assets', ['--only-asset-containers' => true])
            ->expectsOutputToContain('Assets containers imported sucessfully.')
            ->doesntExpectOutputToContain('Assets imported sucessfully.') // doesntExpectOutput
            ->assertExitCode(0);

        $this->assertCount(1, AssetContainerModel::all());
        $this->assertCount(0, AssetModel::all());

        $this->assertDatabaseHas('asset_containers', ['handle' => 'test', 'disk' => 'test']);
        $this->assertDatabaseMissing('assets_meta', ['container' => 'test', 'path' => 'one.txt']);
        $this->assertDatabaseMissing('assets_meta', ['container' => 'test', 'path' => 'two.jpg']);
        $this->assertDatabaseMissing('assets_meta', ['container' => 'test', 'path' => 'subdirectory/other.txt']);
    }

    #[Test]
    public function it_imports_asset_containers_with_console_question()
    {
        $container = tap(AssetContainer::make('test')->disk('test'))->save();
        $container->makeAsset('one.txt')->upload(UploadedFile::fake()->create('one.txt'));
        $container->makeAsset('two.jpg')->upload(UploadedFile::fake()->image('two.jpg'));
        $container->makeAsset('subdirectory/other.txt')->upload(UploadedFile::fake()->create('other.txt'));

        $this->assertCount(0, AssetContainerModel::all());
        $this->assertCount(0, AssetModel::all());

        $this->artisan('statamic:eloquent:import-assets')
            ->expectsQuestion('Do you want to import asset containers?', true)
            ->expectsOutputToContain('Assets containers imported sucessfully.')
            ->expectsQuestion('Do you want to import assets?', false)
            ->doesntExpectOutputToContain('Assets imported sucessfully.')
            ->assertExitCode(0);

        $this->assertCount(1, AssetContainerModel::all());
        $this->assertCount(0, AssetModel::all());

        $this->assertDatabaseHas('asset_containers', ['handle' => 'test', 'disk' => 'test']);
        $this->assertDatabaseMissing('assets_meta', ['container' => 'test', 'path' => 'one.txt']);
        $this->assertDatabaseMissing('assets_meta', ['container' => 'test', 'path' => 'two.jpg']);
        $this->assertDatabaseMissing('assets_meta', ['container' => 'test', 'path' => 'subdirectory/other.txt']);
    }

    #[Test]
    public function it_imports_assets_with_only_assets_argument()
    {
        $container = tap(AssetContainer::make('test')->disk('test'))->save();
        $container->makeAsset('one.txt')->upload(UploadedFile::fake()->create('one.txt'));
        $container->makeAsset('two.jpg')->upload(UploadedFile::fake()->image('two.jpg'));
        $container->makeAsset('subdirectory/other.txt')->upload(UploadedFile::fake()->create('other.txt'));

        $this->assertCount(0, AssetContainerModel::all());
        $this->assertCount(0, AssetModel::all());

        $this->artisan('statamic:eloquent:import-assets', ['--only-assets' => true])
            ->doesntExpectOutputToContain('Assets containers imported sucessfully.')
            ->expectsOutputToContain('Assets imported sucessfully.')
            ->assertExitCode(0);

        $this->assertCount(0, AssetContainerModel::all());
        $this->assertCount(3, AssetModel::all());

        $this->assertDatabaseMissing('asset_containers', ['handle' => 'test', 'disk' => 'test']);
        $this->assertDatabaseHas('assets_meta', ['container' => 'test', 'path' => 'one.txt']);
        $this->assertDatabaseHas('assets_meta', ['container' => 'test', 'path' => 'two.jpg']);
        $this->assertDatabaseHas('assets_meta', ['container' => 'test', 'path' => 'subdirectory/other.txt']);
    }

    #[Test]
    public function it_imports_assets_with_console_question()
    {
        $container = tap(AssetContainer::make('test')->disk('test'))->save();
        $container->makeAsset('one.txt')->upload(UploadedFile::fake()->create('one.txt'));
        $container->makeAsset('two.jpg')->upload(UploadedFile::fake()->image('two.jpg'));
        $container->makeAsset('subdirectory/other.txt')->upload(UploadedFile::fake()->create('other.txt'));

        $this->assertCount(0, AssetContainerModel::all());
        $this->assertCount(0, AssetModel::all());

        $this->artisan('statamic:eloquent:import-assets')
            ->expectsQuestion('Do you want to import asset containers?', false)
            ->doesntExpectOutputToContain('Assets containers imported sucessfully.')
            ->expectsQuestion('Do you want to import assets?', true)
            ->expectsOutputToContain('Assets imported sucessfully.')
            ->assertExitCode(0);

        $this->assertCount(0, AssetContainerModel::all());
        $this->assertCount(3, AssetModel::all());

        $this->assertDatabaseMissing('asset_containers', ['handle' => 'test', 'disk' => 'test']);
        $this->assertDatabaseHas('assets_meta', ['container' => 'test', 'path' => 'one.txt']);
        $this->assertDatabaseHas('assets_meta', ['container' => 'test', 'path' => 'two.jpg']);
        $this->assertDatabaseHas('assets_meta', ['container' => 'test', 'path' => 'subdirectory/other.txt']);
    }
}
