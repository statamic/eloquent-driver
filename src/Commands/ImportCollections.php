<?php

namespace Statamic\Eloquent\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
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
        $this->usingDefaultRepositories(function () {
            $this->importCollections();
        });

        $this->updateEntryOrder();

        $this->newLine();
        $this->info('Collections imported');

        return 0;
    }

    private function usingDefaultRepositories(Closure $callback)
    {
        $originalRepo = get_class(app()->make(CollectionRepositoryContract::class));
        $originalTreeRepo = get_class(app()->make(CollectionTreeRepositoryContract::class));
        $originalCollection = get_class(app()->make(CollectionContract::class));

        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);
        Statamic::repository(CollectionTreeRepositoryContract::class, CollectionTreeRepository::class);
        app()->bind(CollectionContract::class, StacheCollection::class);

        $callback();

        Statamic::repository(CollectionRepositoryContract::class, $originalRepo);
        Statamic::repository(CollectionTreeRepositoryContract::class, $originalTreeRepo);
        app()->bind(CollectionContract::class, $originalCollection);
        Facade::clearResolvedInstance(CollectionRepositoryContract::class);
    }

    private function importCollections()
    {
        $collections = CollectionFacade::all();

        $this->withProgressBar($collections, function ($collection) {
            $lastModified = $collection->fileLastModified();
            EloquentCollection::makeModelFromContract($collection)
                ->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])
                ->save();

            if ($structure = $collection->structure()) {
                $structure->trees()->each(function ($tree) {
                    $lastModified = $tree->fileLastModified();
                    EloquentCollectionTree::makeModelFromContract($tree)
                        ->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])
                        ->save();
                });
            }
        });
    }

    private function updateEntryOrder()
    {
        $this->withProgressBar(CollectionFacade::all(), function ($collections) {
            $collections->updateEntryOrder();
        });
    }
}
