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
        $entry = (new static())
            ->origin($model->origin_id)
            ->locale($model->site)
            ->slug($model->slug)
            ->collection($model->collection)
            ->data($model->data)
            ->blueprint($model->data['blueprint'] ?? null)
            ->published($model->published)
            ->model($model);

        if ($model->date && $entry->collection()->dated()) {
            $entry->date($model->date);
        }

        if (config('statamic.system.track_last_update')) {
            $entry->set('updated_at', $model->updated_at ?? $model->created_at);
        }

        return $entry;
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.entries.model');

        $data = $this->data();

        if ($this->blueprint && $this->collection()->entryBlueprints()->count() > 1) {
            $data['blueprint'] = $this->blueprint;
        }

        $attributes = [
            'origin_id'  => $this->origin()?->id(),
            'site'       => $this->locale(),
            'slug'       => $this->slug(),
            'uri'        => $this->uri(),
            'date'       => $this->hasDate() ? $this->date() : null,
            'collection' => $this->collectionHandle(),
            'data'       => $data->except(EntryQueryBuilder::COLUMNS),
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
}
