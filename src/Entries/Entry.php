<?php

namespace Statamic\Eloquent\Entries;

use Illuminate\Support\Carbon;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Eloquent\Entries\EntryModel as Model;
use Statamic\Entries\Entry as FileEntry;
use Statamic\Facades\Entry as EntryFacade;

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

        $data = $this->data()
            ->merge(method_exists($this, 'computedData') ? $this->computedData() : []);

        $attributes = [
            'origin_id'  => $this->origin()?->id(),
            'site'       => $this->locale(),
            'slug'       => $this->slug(),
            'uri'        => $this->uri(),
            'date'       => $this->hasDate() ? $this->date() : null,
            'collection' => $this->collectionHandle(),
            'blueprint'  => $this->blueprint ?? $this->blueprint()->handle(),
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
                $this->origin = EntryFacade::find($this->origin);
            }

            return $this->origin;
        }

        if (! $this->model?->origin_id) {
            return;
        }

        return EntryFacade::find($this->model->origin_id);
    }
}
