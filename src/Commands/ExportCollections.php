<?php

namespace Statamic\Eloquent\Commands;

use Closure;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Structures\CollectionTreeRepository as CollectionTreeRepositoryContract;
use Statamic\Eloquent\Collections\Collection as EloquentCollection;
use Statamic\Eloquent\Collections\CollectionRepository;
use Statamic\Eloquent\Structures\CollectionTreeRepository;
use Statamic\Entries\Collection as StacheCollection;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Stache;
use Statamic\Statamic;

class ExportCollections extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:export-collections';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export eloquent based collections to flat files.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->usingDefaultRepositories(function () {
            $this->exportCollections();
        });

        $this->newLine();
        $this->info('Collections exported');

        return 0;
    }

    private function usingDefaultRepositories(Closure $callback)
    {
        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);
        Statamic::repository(CollectionTreeRepositoryContract::class, CollectionTreeRepository::class);
        app()->bind(CollectionContract::class, EloquentCollection::class);

        $callback();
    }

    private function exportCollections()
    {
        $collections = CollectionFacade::all();

        $this->withProgressBar($collections, function ($source) {
            $newCollection = (new StacheCollection)
                ->handle($source->handle())
                ->title($source->title())
                ->routes($source->routes)
                ->requiresSlugs($source->requiresSlugs())
                ->titleFormats($source->titleFormats())
                ->mount($source->mount)
                ->dated($source->dated)
                ->sites($source->sites)
                ->template($source->template)
                ->layout($source->layout)
                ->searchIndex($source->searchIndex)
                ->revisionsEnabled($source->revisionsEnabled())
                ->defaultPublishState($source->defaultPublishState)
                ->structureContents($source->structureContents())
                ->sortDirection($source->sortDirection())
                ->sortField($source->sortField())
                ->taxonomies($source->taxonomies)
                ->propagate($source->propagate())
                ->pastDateBehavior($source->pastDateBehavior())
                ->futureDateBehavior($source->futureDateBehavior())
                ->previewTargets($source->previewTargets())
                ->originBehavior($source->originBehavior());

            Stache::store('collections')->save($newCollection);

            if ($structure = $source->structure()) {
                $structure->trees()->each(function ($tree) use ($newCollection) {
                    Blink::forget("collection-{$newCollection->id()}-structure");
                    Stache::store('collection-trees')->save($newCollection->structure()->makeTree($tree->site(), $tree->tree()));
                });
            }
        });
    }
}
