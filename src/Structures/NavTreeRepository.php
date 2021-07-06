<?php

namespace Statamic\Eloquent\Structures;

use Statamic\Contracts\Structures\Tree as TreeContract;
use Statamic\Stache\Repositories\NavTreeRepository as StacheRepository;

class NavTreeRepository extends StacheRepository
{
    public function find(string $handle, string $site): ?TreeContract
    {
        $model = TreeModel::whereHandle($handle)
            ->whereType('navigation')
            ->where('locale', $site)
            ->first();

        return $model
            ? app(TreeContract::class)->fromModel($model)
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

    public static function bindings()
    {
        return [
            TreeContract::class => NavTree::class,
        ];
    }
}
