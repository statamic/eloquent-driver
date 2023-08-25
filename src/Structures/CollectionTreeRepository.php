<?php

namespace Statamic\Eloquent\Structures;

use Statamic\Contracts\Structures\Tree as TreeContract;
use Statamic\Facades\Blink;
use Statamic\Stache\Repositories\CollectionTreeRepository as StacheRepository;

class CollectionTreeRepository extends StacheRepository
{
    public function find(string $handle, string $site): ?TreeContract
    {
        return Blink::once("eloquent-collection-tree-{$handle}-{$site}", function () use ($handle, $site) {
            $model = app('statamic.eloquent.collections.tree_model')::whereHandle($handle)
                ->where('locale', $site)
                ->whereType('collection')
                ->first();

            return $model ? app(app('statamic.eloquent.collections.tree'))->fromModel($model) : null;
        });
    }

    public function save($tree)
    {
        $model = app('statamic.eloquent.collections.tree')::makeModelFromContract($tree);
        $model->save();

        Blink::forget("eloquent-collection-tree-{$model->handle}-{$model->locale}");

        if ($tree instanceof CollectionTree) {
            $tree->model($model->fresh());
        }
    }
}
