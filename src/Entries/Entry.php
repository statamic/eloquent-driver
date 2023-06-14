<?php

namespace Statamic\Eloquent\Entries;

use Illuminate\Support\Carbon;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Eloquent\Entries\EntryModel as Model;
use Statamic\Entries\Entry as FileEntry;

class Entry extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        $data = isset($model->data['__localized_fields']) ? collect($model->data)->only($model->data['__localized_fields']) : $model->data;

        $entry = (new static())
            ->origin($model->origin_id)
            ->locale($model->site)
            ->slug($model->slug)
            ->collection($model->collection)
            ->data($data)
            ->blueprint($model->blueprint ?? $model->data['blueprint'] ?? null)
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

        return $entry;
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.entries.model');

        $data = $this->data();

        $origin = $this->origin();
        $date = $this->hasDate() ? $this->date() : null;

        if ($blueprint = $this->blueprint()) {
            if ($origin) {
                $localizedBlueprintFields = $blueprint
                    ->fields()
                    ->localizable()
                    ->all()
                    ->map
                    ->handle()
                    ->all();

                // remove any fields in entry data that are marked as localized but value is blank
                $localizedFields = [];
                foreach ($localizedBlueprintFields as $blueprintField) {
                    if ($data->get($blueprintField) == '') {
                        $data->forget($blueprintField);
                    } else {
                        $localizedFields[] = $blueprintField;
                    }
                }

                $data = $origin->data()->merge($data);

                $data->put('__localized_fields', $localizedFields);

                if (! in_array('date', $localizedFields)) {
                    $date = $origin->hasDate() ? $origin->date() : null;
                }
            }
        }

        $attributes = [
            'origin_id'  => $this->origin()?->id(),
            'site'       => $this->locale(),
            'slug'       => $this->slug(),
            'uri'        => $this->uri(),
            'date'       => $date,
            'collection' => $this->collectionHandle(),
            'blueprint'  => $this->blueprint ?? $this->blueprint()->handle(),
            'data'       => $data->except(EntryQueryBuilder::COLUMNS)->except(['parent']),
            'published'  => $this->published(),
            'status'     => $this->status(),
            'updated_at' => $this->lastModified(),
            'order'      => $this->order(),
        ];

        if ($id = $this->id()) {
            $attributes['id'] = $id;
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

            return $this;
        }

        $class = app('statamic.eloquent.entries.model');

        if ($this->origin) {
            if (! $this->origin instanceof EntryContract) {
                if ($model = $class::find($this->origin)) {
                    $this->origin = self::fromModel($model);
                }
            }

            return $this->origin;
        }

        if (! $this->model?->origin_id) {
            return;
        }

        if ($model = $class::find($this->model->origin_id)) {
            $this->origin = self::fromModel($model);
        }

        return $this->origin ?? null;
    }

    public function makeLocalization($site)
    {
        $this->localizations = null;

        return parent::makeLocalization($site)
            ->data($this->data());
    }
}
