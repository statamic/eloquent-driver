<?php

namespace Statamic\Eloquent\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
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
    protected $signature = 'statamic:eloquent:export-assets
        {--force : Force the export to run, with all prompts answered "yes"}
        {--only-asset-containers : Only export asset containers}
        {--only-assets : Only export assets}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports eloquent asset containers and assets to file based.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->usingDefaultRepositories(function () {
            $this->exportAssetContainers();
            $this->exportAssets();
        });

        return self::SUCCESS;
    }

    private function usingDefaultRepositories(Closure $callback): void
    {
        Facade::clearResolvedInstance(AssetContainerRepositoryContract::class);
        Facade::clearResolvedInstance(AssetRepositoryContract::class);

        Statamic::repository(AssetContainerRepositoryContract::class, AssetContainerRepository::class);
        Statamic::repository(AssetRepositoryContract::class, AssetRepository::class);

        app()->bind(AssetContainerContract::class, AssetContainer::class);
        app()->bind(AssetContract::class, Asset::class);

        $callback();
    }

    private function exportAssetContainers(): void
    {
        if (! $this->shouldExportAssetContainers()) {
            return;
        }

        $containers = AssetContainerModel::all();

        $this->withProgressBar($containers, function ($model) {
            AssetContainerFacade::make()
                ->title($model->title)
                ->handle($model->handle)
                ->disk($model->disk ?? config('filesystems.default'))
                ->searchIndex($model->settings['search_index'] ?? null)
                ->sourcePreset($model->settings['source_preset'] ?? null)
                ->save();
        });

        $this->newLine();
        $this->info('Asset containers exported');
    }

    private function exportAssets(): void
    {
        if (! $this->shouldExportAssets()) {
            return;
        }

        $assets = AssetModel::all();

        $this->withProgressBar($assets, function ($model) {
            AssetFacade::make()
                ->container($model->container)
                ->path($model->path)
                ->writeMeta($model->meta);
        });

        $this->newLine();
        $this->info('Assets exported');
    }

    private function shouldExportAssetContainers(): bool
    {
        return $this->option('only-asset-containers')
            || ! $this->option('only-assets')
            && ($this->option('force') || $this->confirm('Do you want to export asset containers?'));
    }

    private function shouldExportAssets(): bool
    {
        return $this->option('only-assets')
            || ! $this->option('only-asset-containers')
            && ($this->option('force') || $this->confirm('Do you want to export assets?'));
    }
}
