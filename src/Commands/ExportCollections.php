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
                ->routes($source->routes ?? null)
                ->requiresSlugs($source->slugs ?? true)
                ->titleFormats($source->title_formats ?? null)
                ->mount($source->mount ?? null)
                ->dated($source->dated ?? null)
                ->sites($source->sites ?? null)
                ->template($source->template ?? null)
                ->layout($source->layout ?? null)
                ->searchIndex($source->search_index ?? null)
                ->revisionsEnabled($source->revisions ?? false)
                ->defaultPublishState($source->default_status ?? true)
                ->structureContents($source->structure ?? null)
                ->sortDirection($source->sort_dir ?? null)
                ->sortField($source->sort_field ?? null)
                ->taxonomies($source->taxonomies ?? null)
                ->propagate($source->propagate ?? null)
                ->pastDateBehavior($source->past_date_behavior ?? null)
                ->futureDateBehavior($source->future_date_behavior ?? null)
                ->previewTargets($source->preview_targets ?? [])
                ->originBehavior($source->origin_behavior ?? 'select');

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
