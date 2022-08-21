<?php

namespace Statamic\Eloquent\Entries;

use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Entries\QueryBuilder;
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
        return Blink::once("eloquent-entry-{$id}", function () use ($id) {
            $model = $this->query()->where('id', $id)->first();
            if (! $model) {
                return;
            }

            return app('statamic.eloquent.entries.entry')::fromModel($model);
        });
    }

    public function findByUri(string $uri, string $site = null): ?EntryContract
    {
        return Blink::once("eloquent-entry-{$uri}", function () use ($uri, $site) {
            return parent::findByUri($uri, $site);
        });
    }

    public function save($entry)
    {
        $model = $entry->toModel();
        $model->save();

        $entry->model($model->fresh());
    }

    public function delete($entry)
    {
        $entry->model()->delete();
    }
}
