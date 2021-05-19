<?php

namespace Statamic\Eloquent\Taxonomies;

use Statamic\Eloquent\Taxonomies\TermModel as Model;
use Statamic\Taxonomies\Term as FileEntry;

class Term extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        /** @var Term $term */
        $term = new static;
        $term = $term->slug($model->slug);
        $term = $term->taxonomy($model->taxonomy);
        $term = $term->data($model->data);
        $term = $term->model($model);
        $term = $term->blueprint($model->data['blueprint'] ?? null);


        return $term;
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.terms.model');

        $data = $this->data();

        if ($this->blueprint && $this->taxonomy()->termBlueprints()->count() > 1) {
            $data['blueprint'] = $this->blueprint;
        }
        return $class::findOrNew($this->model?->id)->fill([
            'site' => $this->locale(),
            'slug' => $this->slug(),
            'uri' => $this->uri(),
            'taxonomy' => $this->taxonomy(),
            'data' => $data,
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

    public function lastModified()
    {
        return $this->model->updated_at;
    }
}
