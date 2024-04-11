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

    public function save($entry)
    {
        $model = $entry->toModel();
        $model->save();

        $entry->model($model->fresh());

        Blink::put("eloquent-entry-{$entry->id()}", $entry);
        Blink::put("eloquent-entry-{$entry->uri()}", $entry);
    }

    public function delete($entry)
    {
        Blink::forget("eloquent-entry-{$entry->id()}");
        Blink::forget("eloquent-entry-{$entry->uri()}");

        $entry->model()->delete();
    }
}
