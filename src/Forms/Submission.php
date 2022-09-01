<?php

namespace Statamic\Eloquent\Forms;

use Statamic\Eloquent\Forms\SubmissionModel as Model;
use Statamic\Events\SubmissionDeleted;
use Statamic\Events\SubmissionSaved;
use Statamic\Forms\Submission as FileEntry;

class Submission extends FileEntry
{
    protected $model;

    private $id;

    public static function fromModel(Model $model)
    {
        return (new static)
            ->id($model->id)
            ->date($model->created_at)
            ->data($model->data)
            ->model($model);
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.forms.submission_model');
        $timestamp = (new $class)->fromDateTime($this->date());

        return $class::firstOrNew([
            'form_id' => $this->form->model()->id,
            'created_at' => $timestamp,
        ])->fill([
            'data' => $this->data,
        ]);
    }

    public function model($model = null)
    {
        if (func_num_args() === 0) {
            return $this->model;
        }

        $this->model = $model;

        return $this;
    }

    public function date($date = null)
    {
        if (! is_null($date)) {
            $this->date = $date;
        }

        return $this->date;
    }

    public function save()
    {
        $model = $this->toModel();
        $model->save();

        $this->model($model->fresh());

        SubmissionSaved::dispatch($this);
    }

    public function delete()
    {
        if (! $this->model) {
            $class = app('statamic.eloquent.forms.submission_model');
            $this->model = $class::findOrNew($this->id);
        }

        $this->model->delete();

        SubmissionDeleted::dispatch($this);
    }
}
