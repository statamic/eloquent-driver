<?php

namespace Statamic\Eloquent\Revisions;

use Statamic\Contracts\Revisions\Revision as RevisionContract;
use Statamic\Contracts\Revisions\RevisionQueryBuilder as QueryBuilderContract;
use Statamic\Revisions\RevisionRepository as StacheRepository;

class RevisionRepository extends StacheRepository
{
    public function findWorkingCopyByKey($key)
    {
        $class = app('statamic.eloquent.revisions.model');
        if (! $revision = $class::where(['key' => $key, 'action' => 'working'])->first()) {
            return null;
        }

        return $this->makeRevisionFromFile($key, $revision);
    }

    public function save(RevisionContract $copy)
    {
        if ($copy->isWorkingCopy()) {
            app('statamic.eloquent.revisions.model')::where([
                'key'    => $copy->key(),
                'action' => 'working',
            ])->delete();
        }

        (new Revision)
            ->fromRevisionOrWorkingCopy($copy)
            ->toModel()
            ->save();
    }

    public function delete(RevisionContract $revision)
    {
        $revision->model()?->delete();
    }

    protected function makeRevisionFromFile($key, $model)
    {
        return (new Revision)->fromModel($model);
    }

    public static function bindings(): array
    {
        return [
            RevisionContract::class => Revision::class,
            QueryBuilderContract::class => RevisionQueryBuilder::class,
        ];
    }
}
