<?php

namespace Statamic\Eloquent\Entries;

use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Entries\QueryBuilder;
use Statamic\Eloquent\Jobs\UpdateCollectionEntryOrder;
use Statamic\Eloquent\Jobs\UpdateCollectionEntryParent;
use Statamic\Entries\EntryCollection;
use Statamic\Facades\Blink;
use Statamic\Stache\Repositories\EntryRepository as StacheRepository;

class EntryRepository extends StacheRepository
{
    public static function bindings(): array
    {
        return [
            EntryContract::class => app('statamic.eloquent.entries.entry'),
            QueryBuilder::class => EntryQueryBuilder::class,
        ];
    }

    public function find($id): ?EntryContract
    {
        $blinkKey = "eloquent-entry-{$id}";
        $item = Blink::once($blinkKey, function () use ($id) {
            return $this->query()->where('id', $id)->first();
        });

        if (! $item) {
            Blink::forget($blinkKey);

            return null;
        }

        return $this->substitutionsById[$item->id()] ?? $item;
    }

    public function findByUri(string $uri, ?string $site = null): ?EntryContract
    {
        $blinkKey = 'eloquent-entry-'.md5(urlencode($uri)).($site ? '-'.$site : '');
        $item = Blink::once($blinkKey, function () use ($uri, $site) {
            return parent::findByUri($uri, $site);
        });

        if (! $item) {
            Blink::forget($blinkKey);

            return null;
        }

        return $this->substitutionsById[$item->id()] ?? $item;
    }

    public function whereInId($ids): EntryCollection
    {
        $cached = collect($ids)->flip()->map(fn ($_, $id) => Blink::get("eloquent-entry-{$id}"));
        $missingIds = $cached->reject()->keys();

        $missingById = $this->query()
            ->whereIn('id', $missingIds)
            ->get()
            ->keyBy->id();

        $missingById->each(function ($entry, $id) {
            Blink::put("eloquent-entry-{$id}", $entry);
        });

        $items = $cached
            ->map(fn ($entry, $id) => $entry ?? $missingById->get($id))
            ->filter()
            ->values();

        $this->applySubstitutions($items);

        return EntryCollection::make($items);
    }

    public function save($entry)
    {
        $model = $entry->toModel();
        
        // Extract taxonomy data before saving
        $taxonomyData = $this->extractTaxonomyData($entry);
        
        $model->save();

        // Sync taxonomy relationships if we have taxonomy data
        if (!empty($taxonomyData)) {
            $this->syncTaxonomyRelationships($model, $taxonomyData);
        }

        $entry->model($model->fresh());

        Blink::put("eloquent-entry-{$entry->id()}", $entry);
        Blink::put("eloquent-entry-{$entry->uri()}", $entry);
    }

    protected function extractTaxonomyData($entry)
    {
        if (!$entry->blueprint()) {
            return [];
        }

        $taxonomyFields = $entry->blueprint()->fields()->all()
            ->filter(fn($field) => in_array($field->type(), ['taxonomy', 'terms']))
            ->map->handle()
            ->all();

        return $entry->data()->only($taxonomyFields)->all();
    }

    protected function syncTaxonomyRelationships($model, $taxonomyData)
    {
        if (empty($taxonomyData)) {
            return;
        }

        $termModel = app('statamic.eloquent.terms.model');
        $relationships = [];

        foreach ($taxonomyData as $field => $termSlugs) {
            if (empty($termSlugs)) {
                continue;
            }

            $termSlugs = is_array($termSlugs) ? $termSlugs : [$termSlugs];
            
            foreach ($termSlugs as $slug) {
                if (empty($slug)) {
                    continue;
                }

                // Extract taxonomy from slug if it contains ::
                if (str_contains($slug, '::')) {
                    [$taxonomy, $slug] = explode('::', $slug, 2);
                } else {
                    $taxonomy = $field; // Assume field name is taxonomy handle
                }

                $term = $termModel::where('taxonomy', $taxonomy)
                    ->where('slug', $slug)
                    ->first();

                if ($term) {
                    $relationships[$term->id] = [
                        'taxonomy' => $taxonomy,
                        'field' => $field,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // Sync the relationships (this will remove old ones and add new ones)
        $model->terms()->sync($relationships);
    }

    public function delete($entry)
    {
        Blink::forget("eloquent-entry-{$entry->id()}");
        Blink::forget("eloquent-entry-{$entry->uri()}");

        $entry->model()->delete();
    }

    public function updateUris($collection, $ids = null)
    {
        $ids = collect($ids);

        $collection->queryEntries()
            ->when($ids->isNotEmpty(), fn ($query) => $query->whereIn('id', $ids))
            ->get()
            ->each(fn ($entry) => $entry->model()->update(['uri' => $entry->uri()]));
    }

    public function updateOrders($collection, $ids = null)
    {
        $collection->queryEntries()
            ->when($ids, fn ($query) => $query->whereIn('id', $ids))
            ->get(['id'])
            ->each(function ($entry) {
                $dispatch = UpdateCollectionEntryOrder::dispatch($entry->id());

                $connection = config('statamic.eloquent-driver.collections.update_entry_order_connection', 'default');

                if ($connection != 'default') {
                    $dispatch->onConnection($connection);
                }

                $dispatch->onQueue(config('statamic.eloquent-driver.collections.update_entry_order_queue', 'default'));
            });
    }

    public function updateParents($collection, $ids = null)
    {
        $collection->queryEntries()
            ->when($ids, fn ($query) => $query->whereIn('id', $ids))
            ->get(['id'])
            ->each(function ($entry) {
                $dispatch = UpdateCollectionEntryParent::dispatch($entry->id());

                $connection = config('statamic.eloquent-driver.collections.update_entry_parent_connection', 'default');

                if ($connection != 'default') {
                    $dispatch->onConnection($connection);
                }

                $dispatch->onQueue(config('statamic.eloquent-driver.collections.update_entry_parent_queue', 'default'));
            });
    }

    public function taxonomize($entry)
    {
        if (config('statamic.eloquent-driver.taxonomies.driver') === 'eloquent') {
            return;
        }

        parent::taxonomize($entry);
    }
}
