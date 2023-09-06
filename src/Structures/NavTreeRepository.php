<?php

namespace Statamic\Eloquent\Structures;

use Statamic\Contracts\Structures\NavTree as NavTreeContract;
use Statamic\Contracts\Structures\Tree as TreeContract;
use Statamic\Facades\Blink;
use Statamic\Stache\Repositories\NavTreeRepository as StacheRepository;

class NavTreeRepository extends StacheRepository
{
    public function find(string $handle, string $site): ?TreeContract
    {
        return Blink::once("eloquent-nav-tree-{$handle}-{$site}", function () use ($handle, $site) {
            $model = app('statamic.eloquent.navigations.tree_model')::whereHandle($handle)
                ->whereType('navigation')
                ->where('locale', $site)
                ->first();

            return $model ? app(app('statamic.eloquent.navigations.tree'))->fromModel($model) : null;
        });
    }

    public function save($entry)
    {
        // if we are using flat files for the config, but eloquent for the data
        if (! $entry instanceof NavTree) {
            return parent::save($entry);
        }

        $model = $entry->toModel();
        $model->save();

        Blink::forget("eloquent-nav-tree-{$model->handle}-{$model->locale}");

        $entry->model($model->fresh());
    }

    public function delete($entry)
    {
        // if we are using flat files for the config, but eloquent for the data
        if (! $entry instanceof NavTree) {
            return parent::save($entry);
        }

        $entry->model()->delete();
    }

    public static function bindings()
    {
        return [
            NavTreeContract::class => NavTree::class,
        ];
    }
}
