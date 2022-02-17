<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Assets\AssetContainerRepository as AssetContainerRepositoryContract;
use Statamic\Contracts\Assets\AssetContainer as AssetContainerContract;
use Statamic\Eloquent\Assets\AssetContainer;
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
    protected $signature = 'statamic:eloquent:import-assets';

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

        return 0;
    }

    private function useDefaultRepositories()
    {
        Statamic::repository(AssetContainerRepositoryContract::class, AssetContainerRepository::class);

        // bind to the eloquent container class so we can use toModel()
        app()->bind(AssetContainerContract::class, AssetContainer::class);
    }

    private function importAssetContainers()
    {
        $containers = \Statamic\Facades\AssetContainer::all();
        $bar = $this->output->createProgressBar($containers->count());

        $containers->each(function ($container) use ($bar) {
            $container->toModel()->save();
            $bar->advance();
        });

        $bar->finish();
        $this->line('');
        $this->info('Asset containers imported');
    }
}
