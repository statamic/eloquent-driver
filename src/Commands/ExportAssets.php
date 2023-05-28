<?php

namespace Statamic\Eloquent\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Statamic\Assets\Asset;
use Statamic\Assets\AssetContainer;
use Statamic\Assets\AssetRepository;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Contracts\Assets\AssetContainer as AssetContainerContract;
use Statamic\Contracts\Assets\AssetContainerRepository as AssetContainerRepositoryContract;
use Statamic\Contracts\Assets\AssetRepository as AssetRepositoryContract;
use Statamic\Eloquent\Assets\AssetContainerModel;
use Statamic\Eloquent\Assets\AssetModel;
use Statamic\Facades\Asset as AssetFacade;
use Statamic\Facades\AssetContainer as AssetContainerFacade;
use Statamic\Stache\Repositories\AssetContainerRepository;
use Statamic\Statamic;

class ExportAssets extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:export-assets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports eloquent asset containers and assets to file based.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->usingDefaultRepositories(function () {
            $this->exportAssetContainers();
            $this->exportAssets();
        });

        return 0;
    }

    private function usingDefaultRepositories(Closure $callback)
    {
        Statamic::repository(AssetContainerRepositoryContract::class, AssetContainerRepository::class);
        Statamic::repository(AssetRepositoryContract::class, AssetRepository::class);

        app()->bind(AssetContainerContract::class, AssetContainer::class);
        app()->bind(AssetContract::class, Asset::class);

        $callback();
    }

    private function exportAssetContainers()
    {
        $containers = AssetContainerModel::all();

        $this->withProgressBar($containers, function ($model) {
            AssetContainerFacade::make()
                ->title($model->title)
                ->handle($model->handle)
                ->disk($model->disk ?? config('filesystems.default'))
                ->allowUploads($model->settings['allow_uploads'] ?? null)
                ->allowDownloading($model->settings['allow_downloading'] ?? null)
                ->allowMoving($model->settings['allow_moving'] ?? null)
                ->allowRenaming($model->settings['allow_renaming'] ?? null)
                ->createFolders($model->settings['create_folders'] ?? null)
                ->searchIndex($model->settings['search_index'] ?? null)
                ->save();
        });

        $this->line('');
        $this->info('Asset containers imported');
    }

    private function exportAssets()
    {
        $assets = AssetModel::all();

        $this->withProgressBar($assets, function ($model) {
            $container = Str::before($model->handle, '::');
            $path = Str::after($model->handle, '::');

            AssetFacade::make()
                ->container($container)
                ->path($path)
                ->writeMeta($model->data);
        });

        $this->newLine();
        $this->info('Assets imported');
    }
}
