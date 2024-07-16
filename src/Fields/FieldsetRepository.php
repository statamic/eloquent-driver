<?php

namespace Statamic\Eloquent\Fields;

use Illuminate\Support\Collection;
use Statamic\Facades\Blink;
use Statamic\Fields\Fieldset;
use Statamic\Fields\FieldsetRepository as StacheRepository;

class FieldsetRepository extends StacheRepository
{
    use Traits\StoresAndRetrievesFieldOrder;

    public function all(): Collection
    {
        return Blink::once('eloquent-fieldsets', function () {
            if (count($models = app('statamic.eloquent.fieldsets.model')::get() ?? collect()) === 0) {
                return collect();
            }

            return $models->map(function ($model) {
                return Blink::once("eloquent-fieldset-{$model->handle}", function () use ($model) {
                    $fields = $model->data;
                    $fields['fields'] = $this->applyOrderToBlueprintFields($fields['fields']);

                    return (new Fieldset())
                        ->setHandle($model->handle)
                        ->setContents($fields);
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
        $model = app('statamic.eloquent.fieldsets.model')::firstOrNew([
            'handle' => $fieldset->handle(),
        ]);

        $fields = $fieldset->contents();
        $fields['fields'] = $this->addOrderToBlueprintFields($fields['fields'] ?? []);

        $model->data = $fields;
        $model->save();

        Blink::forget("eloquent-fieldset-{$model->handle}");
    }

    public function deleteModel($fieldset)
    {
        $model = app('statamic.eloquent.fieldsets.model')::where('handle', $fieldset->handle())->first();

        if ($model) {
            $model->delete();
        }

        Blink::forget("eloquent-fieldset-{$model->handle}");
        Blink::forget('eloquent-fieldsets');
    }

    public function getModel($fieldset)
    {
        return $model = app('statamic.eloquent.fieldsets.model')::where('handle', $fieldset->handle())->first();
    }
}
