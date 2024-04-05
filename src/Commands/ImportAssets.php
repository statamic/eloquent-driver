<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
use Statamic\Assets\AssetContainerContents;
use Statamic\Assets\AssetRepository;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Assets\Asset;
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
    protected $signature = 'statamic:eloquent:import-assets {--force : Force the operation to run, with all questions yes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file based asset containers into the database.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->useDefaultRepositories();

        $this->importAssetContainers();
        $this->importAssets();

        return 0;
    }

    private function useDefaultRepositories()
    {
        Facade::clearResolvedInstance(AssetContainerRepositoryContract::class);
        Facade::clearResolvedInstance(AssetRepositoryContract::class);

        Statamic::repository(AssetContainerRepositoryContract::class, AssetContainerRepository::class);
        Statamic::repository(AssetRepositoryContract::class, AssetRepository::class);

        app()->bind(AssetContainerContract::class, AssetContainer::class);
        app()->bind(AssetContract::class, Asset::class);

        app()->bind(AssetContainerContents::class, function ($app) {
            return new AssetContainerContents();
        });
    }

    private function importAssetContainers()
    {
        if (! $this->option('force') && ! $this->confirm('Do you want to import asset containers?')) {
            return;
        }

        $containers = AssetContainerFacade::all();

        $this->withProgressBar($containers, function ($container) {
            $lastModified = $container->fileLastModified();
            $container->toModel()->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])->save();
        });

        $this->line('');
        $this->info('Asset containers imported');
    }

    private function importAssets()
    {
        if (! $this->option('force') && ! $this->confirm('Do you want to import assets?')) {
            return;
        }

        $assets = AssetFacade::all();

        $this->withProgressBar($assets, function ($asset) {
            EloquentAsset::makeModelFromContract($asset);
        });

        $this->newLine();
        $this->info('Assets imported');
    }
}
