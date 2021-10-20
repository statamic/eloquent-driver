<?php

namespace Statamic\Eloquent\Entries;

use Illuminate\Support\Carbon;
use Statamic\Eloquent\Entries\EntryModel as Model;
use Statamic\Entries\Entry as FileEntry;

class Entry extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static)
            ->locale($model->site)
            ->slug($model->slug)
            ->date($model->date)
            ->collection($model->collection)
            ->data($model->data)
            ->blueprint($model->data['blueprint'] ?? null)
            ->published($model->published)
            ->model($model);
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.entries.model');

        $data = $this->data();

        if ($this->blueprint && $this->collection()->entryBlueprints()->count() > 1) {
            $data['blueprint'] = $this->blueprint;
        }

        return $class::findOrNew($this->id())->fill([
            'id' => $this->id(),
            'origin_id' => $this->originId(),
            'site' => $this->locale(),
            'slug' => $this->slug(),
            'uri' => $this->uri(),
            'date' => $this->hasDate() ? $this->date() : null,
            'collection' => $this->collectionHandle(),
            'data' => $data->except(EntryQueryBuilder::COLUMNS),
            'published' => $this->published(),
            'status' => $this->status(),
        ]);
    }

    public function model($model = null)
    {
        if (func_num_args() === 0) {
            return $this->model;
        }

        $this->model = $model;

        $this->id($model->id);

        return $this;
    }

    /**
     * This overwrite is needed to prevent Statamic to save updated_at also into the data. We track updated_at already in the database.
     *
     * @param null $user
     * @return $this|Entry|FileEntry|\Statamic\Taxonomies\LocalizedTerm
     */
    public function updateLastModified($user = null)
    {
        if (! config('statamic.system.track_last_update')) {
            return $this;
        }

        $user
            ? $this->set('updated_by', $user->id())
            : $this->remove('updated_by');

        // ensure 'updated_at' does not exists in the data of the entry.
        $this->remove('updated_at');

        return $this;
    }

    public function lastModified()
    {
        return $this->model->updated_at;
    }

    public function origin($origin = null)
    {
        if (func_num_args() > 0) {
            $this->origin = $origin;

            return $this;
        }

        if ($this->origin) {
            return $this->origin;
        }

        if (! $this->model->origin) {
            return;
        }

        return self::fromModel($this->model->origin);
    }

    public function originId()
    {
        return optional($this->origin)->id() ?? optional($this->model)->origin_id;
    }

    public function hasOrigin()
    {
        return $this->originId() !== null;
    }
}
