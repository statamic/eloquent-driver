<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Structures\CollectionTreeRepository as CollectionTreeRepositoryContract;
use Statamic\Eloquent\Collections\Collection as EloquentCollection;
use Statamic\Eloquent\Structures\CollectionTree as EloquentCollectionTree;
use Statamic\Entries\Collection as StacheCollection;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Stache\Repositories\CollectionRepository;
use Statamic\Stache\Repositories\CollectionTreeRepository;
use Statamic\Statamic;

class ImportCollections extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:import-collections';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file based collections into the database.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->useDefaultRepositories();

        $this->importCollections();

        return 0;
    }

    private function useDefaultRepositories()
    {
        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);
        Statamic::repository(CollectionTreeRepositoryContract::class, CollectionTreeRepository::class);
        app()->bind(CollectionContract::class, StacheCollection::class);
    }

    private function importCollections()
    {
        $collections = CollectionFacade::all();
        $bar = $this->output->createProgressBar($collections->count());

        $collections->each(function ($collection) use ($bar) {
            $model = tap(EloquentCollection::makeModelFromContract($collection))->save();
            if ($structure = $collection->structure()) {
                $structure->trees()->each(function ($tree) {
                    EloquentCollectionTree::makeModelFromContract($tree)->save();
                });
            }
            $bar->advance();
        });

        $bar->finish();
        $this->line('');
        $this->info('Collections imported');
    }
}
