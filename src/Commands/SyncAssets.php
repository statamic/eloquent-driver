<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Assets\AssetContainer;
use Statamic\Eloquent\Assets\AssetModel;
use Statamic\Facades;

class SyncAssets extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:sync-assets {--container=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync assets in an asset container with your database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Facades\AssetContainer::all()
            ->reject(fn ($container) => $this->option('container') != 'all' && $this->option('container') != $container->handle())
            ->each(fn ($container) => $this->processContainer($container));

        $this->info('Complete');
    }

    private function processContainer(AssetContainer $container)
    {
        $this->info("Container: {$container->handle()}");

        $this->processFolder($container);
    }

    private function processFolder(AssetContainer $container, $folder = '')
    {
        $this->line("Processing folder: {$folder}");

        // get raw listing of this folder, avoiding any of statamic's asset container caching
        $files = collect($container->disk()->filesystem()->listContents($folder))
            ->reject(fn ($item) => $item['type'] != 'file')
            ->pluck('path');

        // ensure we have an asset for any paths
        $files->each(function ($file) use ($container) {
            $this->info($file);

            if (Str::startsWith($file, '.')) {
                return;
            }

            $asset = Facades\Asset::make()
                ->container($container->handle())
                ->path($file)
                ->saveQuietly();
        });

        // delete any assets we have a db entry for that no longer exist
        AssetModel::query()
            ->where('container', $container->handle())
            ->where('folder', $folder)
            ->chunk(100, function ($assets) use ($files) {
                $assets->each(function ($asset) use ($files) {
                    if (! $files->contains($asset->path)) {
                        $this->error("Deleting {$asset->path}");

                        $asset->delete();
                    }
                });
            });

        // process any sub-folders of this folder
        $container->folders($folder)
            ->each(function ($subfolder) use ($container, $folder) {
                if ($folder != $subfolder && (strlen($subfolder) > strlen($folder))) {
                    $this->processFolder($container, $subfolder);
                }
            });
    }
}
