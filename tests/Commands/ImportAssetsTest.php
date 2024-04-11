<?php

namespace Tests\Commands;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Contracts\Assets\AssetContainer as AssetContainerContract;
use Statamic\Contracts\Assets\AssetContainerRepository as AssetContainerRepositoryContract;
use Statamic\Contracts\Assets\AssetRepository as AssetRepositoryContract;
use Statamic\Eloquent\Assets\AssetContainerModel;
use Statamic\Eloquent\Assets\AssetModel;
use Statamic\Eloquent\Forms\FormModel;
use Statamic\Eloquent\Forms\SubmissionModel;
use Tests\PreventSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportAssetsTest extends TestCase
{
    use PreventSavingStacheItemsToDisk;

    private $tempDir;

    public function setUp(): void
    {
        parent::setUp();

        config(['filesystems.disks.test' => [
            'driver' => 'local',
            'root' => $this->tempDir = __DIR__.'/tmp',
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

    public function tearDown(): void
    {
        app('files')->deleteDirectory($this->tempDir);

        parent::tearDown();
    }

    /** @test */
    public function it_imports_asset_containers_and_assets()
    {
        $container = tap(\Statamic\Facades\AssetContainer::make('test')->disk('test'))->save();
        $container->makeAsset('one.txt')->upload(UploadedFile::fake()->create('one.txt'));
        $container->makeAsset('two.jpg')->upload(UploadedFile::fake()->image('two.jpg'));
        $container->makeAsset('subdirectory/other.txt')->upload(UploadedFile::fake()->create('other.txt'));

        $this->assertCount(0, AssetContainerModel::all());
        $this->assertCount(0, AssetModel::all());

        $this->artisan('statamic:eloquent:import-assets')
            ->expectsQuestion('Do you want to import asset containers?', true)
            ->expectsOutput('Asset containers imported')
            ->expectsQuestion('Do you want to import assets?', true)
            ->expectsOutput('Assets imported')
            ->assertExitCode(0);

        $this->assertCount(1, AssetContainerModel::all());
        $this->assertCount(3, AssetModel::all());
    }

    /** @test */
    public function it_imports_asset_containers()
    {
        $container = tap(\Statamic\Facades\AssetContainer::make('test')->disk('test'))->save();
        $container->makeAsset('one.txt')->upload(UploadedFile::fake()->create('one.txt'));
        $container->makeAsset('two.jpg')->upload(UploadedFile::fake()->image('two.jpg'));
        $container->makeAsset('subdirectory/other.txt')->upload(UploadedFile::fake()->create('other.txt'));

        $this->assertCount(0, AssetContainerModel::all());
        $this->assertCount(0, AssetModel::all());

        $this->artisan('statamic:eloquent:import-assets', ['--only-asset-containers' => true])
            ->expectsOutput('Asset containers imported')
            ->doesntExpectOutput('Assets imported')
            ->assertExitCode(0);

        $this->assertCount(1, AssetContainerModel::all());
        $this->assertCount(0, AssetModel::all());
    }

    /** @test */
    public function it_imports_assets()
    {
        $container = tap(\Statamic\Facades\AssetContainer::make('test')->disk('test'))->save();
        $container->makeAsset('one.txt')->upload(UploadedFile::fake()->create('one.txt'));
        $container->makeAsset('two.jpg')->upload(UploadedFile::fake()->image('two.jpg'));
        $container->makeAsset('subdirectory/other.txt')->upload(UploadedFile::fake()->create('other.txt'));

        $this->assertCount(0, AssetContainerModel::all());
        $this->assertCount(0, AssetModel::all());

        $this->artisan('statamic:eloquent:import-assets', ['--only-assets' => true])
            ->doesntExpectOutput('Asset containers imported')
            ->expectsOutput('Assets imported')
            ->assertExitCode(0);

        $this->assertCount(0, AssetContainerModel::all());
        $this->assertCount(3, AssetModel::all());
    }
}
