<?php

namespace Statamic\Eloquent\Globals;

use Statamic\Contracts\Globals\GlobalSet as GlobalSetContract;
use Statamic\Contracts\Globals\Variables as VariablesContract;
use Statamic\Facades\Blink;
use Statamic\Globals\GlobalCollection;
use Statamic\Stache\Repositories\GlobalRepository as StacheRepository;

class GlobalRepository extends StacheRepository
{
    protected function transform($items, $columns = [])
    {
        return GlobalCollection::make($items)->map(function ($model) {
            return Blink::once("eloquent-globalsets-{$model->handle}", function () use ($model) {
                return app(GlobalSetContract::class)::fromModel($model);
            });
        });
    }

    public function find($handle): ?GlobalSetContract
    {
        return Blink::once("eloquent-globalsets-{$handle}", function () use ($handle) {
            $model = app('statamic.eloquent.global_sets.model')::whereHandle($handle)->first();
            if (! $model) {
                return;
            }

            return app(GlobalSetContract::class)->fromModel($model);
        });
    }

    public function findByHandle($handle): ?GlobalSetContract
    {
        return $this->find($handle);
    }

    public function all(): GlobalCollection
    {
        return Blink::once('eloquent-globalsets', function () {
            return $this->transform(app('statamic.eloquent.global_sets.model')::all());
        });
    }

    public function save($entry)
    {
        $model = $entry->toModel();
        $model->save();

        $entry->model($model->fresh());

        Blink::forget("eloquent-globalsets-{$model->handle}");
    }

    public function delete($entry)
    {
        $entry->model()->delete();

        Blink::forget("eloquent-globalsets-{$entry->handle()}");
        Blink::forget('eloquent-globalsets');
    }

    public static function bindings(): array
    {
        return [
            GlobalSetContract::class => GlobalSet::class,
            VariablesContract::class => Variables::class,
        ];
    }
}
