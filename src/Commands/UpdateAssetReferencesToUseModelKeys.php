<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Assets\AssetReferenceUpdater;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Assets\AssetContainer;
use Statamic\Facades;

class UpdateAssetReferencesToUseModelKeys extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:update-asset-references-to-use-model-keys {--container=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update container::path references to container::asset_model_key';

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

        $container->queryAssets()->get()->each(function ($item) use ($container) {
            return AssetReferenceUpdater::item($item)
                ->filterByContainer($container)
                ->updateReferences($item->path(), $item->id());
        });
    }
}
