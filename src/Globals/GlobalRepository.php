<?php

namespace Statamic\Eloquent\Globals;

use Statamic\Contracts\Globals\GlobalSet as GlobalSetContract;
use Statamic\Contracts\Globals\Variables as VariablesContract;
use Statamic\Globals\GlobalCollection;
use Statamic\Stache\Repositories\GlobalRepository as StacheRepository;

class GlobalRepository extends StacheRepository
{
    protected function transform($items, $columns = [])
    {
        return GlobalCollection::make($items)->map(function ($model) {
            return app(GlobalSetContract::class)::fromModel($model);
        });
    }

    public function find($handle): ?GlobalSetContract
    {
        return app(GlobalSetContract::class)->fromModel(app('statamic.eloquent.global-sets.model')::whereHandle($handle)->firstOrFail());
    }

    public function findByHandle($handle): ?GlobalSetContract
    {
        return app(GlobalSetContract::class)->fromModel(app('statamic.eloquent.global-sets.model')::whereHandle($handle)->firstOrFail());
    }

    public function all(): GlobalCollection
    {
        return $this->transform(app('statamic.eloquent.global-sets.model')::all());
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

    public static function bindings(): array
    {
        return [
            GlobalSetContract::class => GlobalSet::class,
            VariablesContract::class => Variables::class,
        ];
    }
}
