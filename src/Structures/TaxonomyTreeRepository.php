<?php

namespace Statamic\Eloquent\Structures;

use Statamic\Contracts\Structures\TaxonomyTree as TaxonomyTreeContract;
use Statamic\Contracts\Structures\Tree as TreeContract;
use Statamic\Facades\Blink;
use Statamic\Facades\Site;
use Statamic\Stache\Repositories\TaxonomyTreeRepository as StacheRepository;

class TaxonomyTreeRepository extends StacheRepository
{
    public function find(string $handle): ?TreeContract
    {
        $site = Site::default()->handle();

        return Blink::once("eloquent-taxonomy-tree-{$handle}-{$site}", function () use ($handle, $site) {
            $model = app('statamic.eloquent.taxonomies.tree_model')::whereHandle($handle)
                ->where('locale', $site)
                ->whereType('taxonomy')
                ->first();

            return $model ? app(app('statamic.eloquent.taxonomies.tree'))->fromModel($model) : null;
        });
    }

    public function save($tree)
    {
        $model = $tree->toModel();
        $model->save();

        Blink::forget("eloquent-taxonomy-tree-{$model->handle}-{$model->locale}");

        $tree->model($model->fresh());

        return true;
    }

    public function delete($tree)
    {
        if (! $tree instanceof TaxonomyTree) {
            return parent::delete($tree);
        }

        Blink::forget("eloquent-taxonomy-tree-{$tree->handle()}-{$tree->locale()}");

        $tree->model()?->delete();

        return true;
    }

    public static function bindings()
    {
        return [
            TaxonomyTreeContract::class => TaxonomyTree::class,
        ];
    }
}
