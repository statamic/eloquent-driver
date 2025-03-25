<?php

namespace Statamic\Eloquent\Entries;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Entries\Entry as FileEntry;
use Statamic\Facades\Blink;
use Statamic\Facades\Entry as EntryFacade;

class Entry extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        $data = isset($model->data['__localized_fields']) ? collect($model->data)->only($model->data['__localized_fields']) : $model->data;

        foreach ((new self)->getDataColumnMappings($model) as $key) {
            $data[$key] = $model->$key;
        }

        $entry = (new static)
            ->origin($model->origin_id)
            ->locale($model->site)
            ->slug($model->slug)
            ->collection($model->collection)
            ->data($data)
            ->blueprint($model->blueprint ?? $model->data['blueprint'] ?? null)
            ->template($model->data['template'] ?? null)
            ->published($model->published)
            ->model($model);

        if ($model->date && $entry->collection()->dated()) {
            $entry->date($model->date);
        }

        if (config('statamic.system.track_last_update')) {
            if ($updatedAt = $model->updated_at ?? $model->created_at) {
                $entry->set('updated_at', $updatedAt->timestamp);
            }
        }

        return $entry->syncOriginal();
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(EntryContract $source)
    {
        $class = app('statamic.eloquent.entries.model');

        $data = $source->data()
            ->merge(method_exists($source, 'computedData') ? $source->computedData() : []);

        $date = $source->hasDate() ? $source->date() : null;

        $origin = $source->origin();

        if ($source->hasOrigin()) {
            if ($blueprint = $source->blueprint()) {
                $localizedBlueprintFields = $blueprint
                    ->fields()
                    ->localizable()
                    ->all()
                    ->map
                    ->handle()
                    ->all();

                $originData = $origin->data();

                // remove any fields in entry data that are marked as localized but value is present, and does not match origin
                $localizedFields = [];
                foreach ($localizedBlueprintFields as $blueprintField) {
                    if ($data->has($blueprintField)) {
                        if ($data->get($blueprintField) === $originData->get($blueprintField)) {
                            $data->forget($blueprintField);
                        } else {
                            $localizedFields[] = $blueprintField;
                        }
                    }
                }

                $data = $originData->merge($data);

                $data->put('__localized_fields', $localizedFields);

                if (! in_array('date', $localizedFields)) {
                    $date = $origin->hasDate() ? $origin->date() : null;
                }
            }
        }

        if ($parent = $source->parent()) {
            $data->put('parent', (string) $parent->id);
        }

        $dataMappings = (new self)->getDataColumnMappings(new $class);

        $attributes = [];

        if ($id = $source->id()) {
            $attributes['id'] = $id;

            // Ensure that when calling $source->uri() that it doesn't use the cached value.
            Blink::store('entry-uris')->forget($source->id());
        }

        // disable the uri cache so any slug updates give us the latest slug
        $source->structure()?->in($source->locale())->disableUriCache();

        $attributes = [
            ...$attributes,
            'origin_id' => $origin?->id(),
            'site' => $source->locale(),
            'slug' => $source->slug(),
            'uri' => $source->uri() ?? $source->routableUri(),
            'date' => $date,
            'collection' => $source->collectionHandle(),
            'blueprint' => $source->blueprint ?? $source->blueprint()->handle(),
            'data' => $data->except(array_merge(EntryQueryBuilder::COLUMNS, $dataMappings)),
            'published' => $source->published(),
            'updated_at' => $source->lastModified(),
            'order' => $source->order(),
        ];

        if ($template = $source->get('template', $source->template)) {
            $attributes['data']->put('template', $template);
        }

        $attributes['data'] = $attributes['data']->filter(fn ($v) => $v !== null);

        foreach ($dataMappings as $key) {
            $attributes[$key] = $data->get($key);
        }

        return $class::findOrNew($id)->fill($attributes);
    }

    public function model($model = null)
    {
        if (func_num_args() === 0) {
            return $this->model;
        }

        $this->model = $model;

        if (! is_null($model)) {
            $this->id($model->id);
        }

        return $this;
    }

    public function fileLastModified()
    {
        return $this->model?->updated_at ?? Carbon::now();
    }

    public function lastModified()
    {
        return $this->fileLastModified();
    }

    public function origin($origin = null)
    {
        if (func_num_args() > 0) {
            $this->origin = $origin;

            if ($this->model) {
                $this->model->origin_id = $origin instanceof EntryContract ? $origin->id() : $origin;
            }

            return $this;
        }

        if ($this->origin) {
            if (! $this->origin instanceof EntryContract) {
                $this->origin = EntryFacade::find($this->origin);
            }

            return $this->origin;
        }

        if (! $this->model?->origin_id) {
            return;
        }

        return EntryFacade::find($this->model->origin_id);
    }

    public function makeLocalization($site)
    {
        $this->localizations = null;

        return parent::makeLocalization($site)
            ->data($this->data());
    }

    public function getDataColumnMappings(Model $model)
    {
        if (! config('statamic.eloquent-driver.entries.map_data_to_columns', false)) {
            return [];
        }

        return Blink::once("eloquent-schema-{$model->getTable()}", function () use ($model) {
            $schema = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());

            return collect($schema)->reject(fn ($value) => in_array($value, EntryQueryBuilder::COLUMNS))->all();
        });
    }
}
