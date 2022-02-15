<?php

namespace Statamic\Eloquent\Structures;

use Statamic\Contracts\Structures\Tree as TreeContract;
use Statamic\Stache\Repositories\NavTreeRepository as StacheRepository;

class NavTreeRepository extends StacheRepository
{
    public function find(string $handle, string $site): ?TreeContract
    {
        $model = app('statamic.eloquent.navigations.tree-model')::whereHandle($handle)
            ->whereType('navigation')
            ->where('locale', $site)
            ->first();

        return $model
            ? app(app('statamic.eloquent.navigations.tree'))->fromModel($model)
            : null;
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
