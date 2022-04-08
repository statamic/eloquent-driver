<?php

namespace Statamic\Eloquent\Structures;

use Statamic\Contracts\Structures\Tree as TreeContract;
use Statamic\Facades\Blink;
use Statamic\Stache\Repositories\NavTreeRepository as StacheRepository;

class NavTreeRepository extends StacheRepository
{
    public function find(string $handle, string $site): ?TreeContract
    {
        return Blink::once("eloquent-nav-tree-{$handle}-{$site}", function() use ($handle, $site) {
        
            $model = app('statamic.eloquent.navigations.tree-model')::whereHandle($handle)
                ->whereType('navigation')
                ->where('locale', $site)
                ->first();
    
            return $model
                ? app(app('statamic.eloquent.navigations.tree'))->fromModel($model)
                : null;
                    
        });
    }

    public function save($entry)
    {
        $model = $entry->toModel();

        $model->save();
        
        Blink::forget("eloquent-nav-tree-{$model->handle}-{$model->locale}");

        $entry->model($model->fresh());
    }

    public function delete($entry)
    {
        $entry->model()->delete();
    }
}
