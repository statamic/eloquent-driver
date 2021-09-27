<?php

namespace Statamic\Eloquent\Fieldsets;

use Illuminate\Support\Collection;
use Statamic\Facades\Blink;
use Statamic\Fields\Fieldset;
use Statamic\Fields\FieldsetRepository as BaseFieldsetRepository;

class FieldsetRepository extends BaseFieldsetRepository
{

    public function all(): Collection
    {
        return Blink::once('fieldsets', function () {
            if (count(($models = FieldsetModel::get() ?? collect())) === 0) {
                return collect();
            }

            return $models->map(function ($model) {
                return (new Fieldset)
                    ->setHandle($model->handle)
                    ->setContents($model->data);
            });
        });
    }

    public function find($handle): ?Fieldset
    {
        if ($cached = array_get($this->fieldsets, $handle)) {
            return $cached;
        }

        $handle = str_replace('/', '.', $handle);

        if (($model = FieldsetModel::where('handle', $handle)->first()) === null) {
            return null;
        }

        $fieldset = (new Fieldset)
            ->setHandle($handle)
            ->setContents($model->data);

        $this->fieldsets[$handle] = $fieldset;

        return $fieldset;
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
        $model = FieldsetModel::firstOrNew([
            'handle' => $fieldset->handle(),
        ]);
        $model->data = $fieldset->contents();
        $model->save();
    }

    public function deleteModel($fieldset)
    {
        $model = FieldsetModel::where('handle', $fieldset->handle())->first();
        $model->delete();
    }
}
