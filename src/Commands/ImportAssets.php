<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
use Statamic\Assets\AssetContainerContents;
use Statamic\Assets\AssetRepository;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Assets\AssetContainer as AssetContainerContract;
use Statamic\Contracts\Assets\AssetContainerRepository as AssetContainerRepositoryContract;
use Statamic\Contracts\Assets\AssetRepository as AssetRepositoryContract;
use Statamic\Eloquent\Assets\Asset as EloquentAsset;
use Statamic\Eloquent\Assets\AssetContainer;
use Statamic\Facades\Asset as AssetFacade;
use Statamic\Facades\AssetContainer as AssetContainerFacade;
use Statamic\Stache\Repositories\AssetContainerRepository;
use Statamic\Statamic;

class ImportAssets extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:import-assets
        {--force : Force the import to run, with all prompts answered "yes"}
        {--only-asset-containers : Only import asset containers}
        {--only-assets : Only import assets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file-based asset containers & asset metadata into the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->useDefaultRepositories();

        $this->importAssetContainers();
        $this->importAssets();

        return 0;
    }

    private function useDefaultRepositories(): void
    {
        Facade::clearResolvedInstance(AssetContainerRepositoryContract::class);
        Facade::clearResolvedInstance(AssetRepositoryContract::class);

        Statamic::repository(AssetContainerRepositoryContract::class, AssetContainerRepository::class);
        Statamic::repository(AssetRepositoryContract::class, AssetRepository::class);

        app()->bind(AssetContainerContract::class, AssetContainer::class);
        app()->bind(AssetContainerContents::class, fn ($app) => new AssetContainerContents);
    }

    private function importAssetContainers(): void
    {
        if (! $this->shouldImportAssetContainers()) {
            return;
        }

        $this->withProgressBar(AssetContainerFacade::all(), function ($container) {
            AssetContainer::makeModelFromContract($container)?->save();
        });

        $this->components->info('Assets containers imported sucessfully');
    }

    private function importAssets(): void
    {
        if (! $this->shouldImportAssets()) {
            return;
        }

        $this->withProgressBar(AssetFacade::all(), function ($asset) {
            EloquentAsset::makeModelFromContract($asset)?->save();
        });

        $this->components->info('Assets imported sucessfully');
    }

    private function shouldImportAssetContainers(): bool
    {
        return $this->option('only-asset-containers')
            || ! $this->option('only-assets')
            && ($this->option('force') || $this->confirm('Do you want to import asset containers?'));
    }

    private function shouldImportAssets(): bool
    {
        return $this->option('only-assets')
            || ! $this->option('only-asset-containers')
            && ($this->option('force') || $this->confirm('Do you want to import assets?'));
    }
}
