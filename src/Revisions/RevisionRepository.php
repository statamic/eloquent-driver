<?php

namespace Statamic\Eloquent\Revisions;

use Statamic\Contracts\Revisions\Revision as RevisionContract;
use Statamic\Revisions\RevisionRepository as StacheRepository;
use Statamic\Revisions\WorkingCopy;

class RevisionRepository extends StacheRepository
{
    public function make(): RevisionContract
    {
        return new (app('statamic.eloquent.revisions.model'));
    }

    public function whereKey($key)
    {
        return app('statamic.eloquent.revisions.model')::where('key', $key)
            ->orderBy('created_at')
            ->get()
            ->map(function ($revision) use ($key) {
                return $this->makeRevisionFromFile($key, $revision);
            })->keyBy(function ($revision) {
                return $revision->date()->timestamp;
            });
    }

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
        if ($copy instanceof WorkingCopy) {
            app('statamic.eloquent.revisions.model')::where([
                'key'    => $copy->key(),
                'action' => 'working',
            ])->delete();
        }

        $revision = (new Revision)
            ->fromRevisionOrWorkingCopy($copy)
            ->toModel()
            ->save();
    }

    public function delete(RevisionContract $revision)
    {
        if ($revision instanceof WorkingCopy) {
            $this->findWorkingCopyByKey($revision->key())?->delete();

            return;
        }

        $revision->model?->delete();
    }

    protected function makeRevisionFromFile($key, $model)
    {
        return (new Revision)->fromModel($model);
    }

    public static function bindings(): array
    {
        return [
            RevisionContract::class => Revision::class,
        ];
    }
}
