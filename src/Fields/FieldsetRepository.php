<?php

namespace Statamic\Eloquent\Fields;

use Illuminate\Support\Collection;
use Statamic\Eloquent\Fields\Concerns\PreservesSetOrder;
use Statamic\Facades\Blink;
use Statamic\Fields\Fieldset;
use Statamic\Fields\FieldsetRepository as StacheRepository;
use Statamic\Support\Str;

class FieldsetRepository extends StacheRepository
{
    use PreservesSetOrder;

    public function all(): Collection
    {
        return Blink::once('eloquent-fieldsets', function () {
            if (count($models = app('statamic.eloquent.fieldsets.model')::get() ?? collect()) === 0) {
                return collect();
            }

            return $models->map(function ($model) {
                return Blink::once("eloquent-fieldset-{$model->handle}", function () use ($model) {
                    $handle = $model->handle;

                    if (Str::startsWith($handle, 'vendor.')) {
                        $handle = Str::of($handle)->after('vendor.')->replaceFirst('.', '::');
                    }

                    return (new Fieldset)
                        ->setHandle($handle)
                        ->setContents($this->updateOrderFromFieldsetContents($model->data));
                });
            });
        })
            ->merge($this->getNamespacedFieldsets());
    }

    public function find($handle): ?Fieldset
    {
        $handle = str_replace('/', '.', $handle);

        if (Str::startsWith($handle, 'vendor.')) {
            $handle = Str::of($handle)->after('vendor.')->replaceFirst('.', '::');
        }

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

        $model->data = $this->addOrderToFieldsetContents($fieldset->contents());
        $model->save();

        Blink::forget("eloquent-fieldset-{$model->handle}");
    }

    private function addOrderToFieldsetContents($contents)
    {
        if (isset($contents['sections']) && is_array($contents['sections'])) {
            $contents['sections'] = collect($contents['sections'])
                ->map(function ($section) {
                    if (isset($section['fields']) && is_array($section['fields'])) {
                        $section['fields'] = $this->addOrderToSets($section['fields']);
                    }

                    return $section;
                })
                ->toArray();

            return $contents;
        }

        if (isset($contents['fields']) && is_array($contents['fields'])) {
            $contents['fields'] = $this->addOrderToSets($contents['fields']);
        }

        return $contents;
    }

    private function updateOrderFromFieldsetContents($contents)
    {
        if (isset($contents['sections']) && is_array($contents['sections'])) {
            $contents['sections'] = collect($contents['sections'])
                ->map(function ($section) {
                    if (isset($section['fields']) && is_array($section['fields'])) {
                        $section['fields'] = $this->updateOrderFromSets($section['fields']);
                    }

                    return $section;
                })
                ->toArray();

            return $contents;
        }

        if (isset($contents['fields']) && is_array($contents['fields'])) {
            $contents['fields'] = $this->updateOrderFromSets($contents['fields']);
        }

        return $contents;
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
}
