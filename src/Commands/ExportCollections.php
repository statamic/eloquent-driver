<?php

namespace Statamic\Eloquent\Commands;

use Closure;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Structures\CollectionTreeRepository as CollectionTreeRepositoryContract;
use Statamic\Eloquent\Collections\Collection as EloquentCollection;
use Statamic\Eloquent\Collections\CollectionModel;
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
        $collections = CollectionModel::all();

        $this->withProgressBar($collections, function ($model) {
            $source = (object) $model->settings;
            $newCollection = (new StacheCollection)
                ->handle($model->handle)
                ->title($model->title)
                ->routes($source->routes)
                ->requiresSlugs($source->slugs)
                ->titleFormats($source->title_formats)
                ->mount($source->mount)
                ->dated($source->dated)
                ->sites($source->sites)
                ->template($source->template)
                ->layout($source->layout)
                ->searchIndex($source->search_index)
                ->revisionsEnabled($source->revisions)
                ->defaultPublishState($source->default_status)
                ->structureContents($source->structure)
                ->sortDirection($source->sort_dir)
                ->sortField($source->sort_field)
                ->taxonomies($source->taxonomies)
                ->propagate($source->propagate)
                ->pastDateBehavior($source->past_date_behavior)
                ->futureDateBehavior($source->future_date_behavior)
                ->previewTargets($source->preview_targets)
                ->originBehavior($source->origin_behavior);

            Stache::store('collections')->save($newCollection);

            if ($source->structure) {
                $collection = EloquentCollection::fromModel($model);

                $collection->structure()->trees()->each(function ($tree) use ($newCollection) {
                    Blink::forget("collection-{$newCollection->id()}-structure");
                    Stache::store('collection-trees')->save($newCollection->structure()->makeTree($tree->site(), $tree->tree()));
                });
            }
        });
    }
}
