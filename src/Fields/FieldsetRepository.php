<?php

namespace Statamic\Eloquent\Fields;

use Illuminate\Support\Collection;
use Statamic\Facades\Blink;
use Statamic\Fields\Fieldset;
use Statamic\Fields\FieldsetRepository as StacheRepository;

class FieldsetRepository extends StacheRepository
{
    public function all(): Collection
    {
        return Blink::once('eloquent-fieldsets', function () {
            if (count($models = app('statamic.eloquent.blueprints.fieldset_model')::get() ?? collect()) === 0) {
                return collect();
            }

            return $models->map(function ($model) {
                return Blink::once("eloquent-fieldset-{$model->handle}", function () use ($model) {
                    return (new Fieldset())
                        ->setHandle($model->handle)
                        ->setContents($model->data);
                });
            });
        });
    }

    public function find($handle): ?Fieldset
    {
        $handle = str_replace('/', '.', $handle);

        return $this->all()->filter(function ($fieldset) use ($handle) {
            return $fieldset->handle() == $handle;
        })->first();
    }

    public function save(Fieldset $fieldset)
    {
        $this->updateModel($fieldset);
    }

    public function delete(Fieldset $fieldset)
    {
        $this->deleteModel($fieldset);
    }

    public function updateModel($fieldset)
    {
        $model = app('statamic.eloquent.blueprints.fieldset_model')::firstOrNew([
            'handle' => $fieldset->handle(),
        ]);

        $model->data = $fieldset->contents();
        $model->save();

        Blink::forget("eloquent-fieldset-{$model->handle}");
    }

    public function deleteModel($fieldset)
    {
        $model = app('statamic.eloquent.blueprints.fieldset_model')::where('handle', $fieldset->handle())->first();

        if ($model) {
            $model->delete();
        }

        Blink::forget("eloquent-fieldset-{$model->handle}");
        Blink::forget('eloquent-fieldsets');
    }
}
