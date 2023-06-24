<?php

namespace Statamic\Eloquent\Forms;

use Illuminate\Support\Carbon;
use Statamic\Eloquent\Forms\SubmissionModel as Model;
use Statamic\Events\SubmissionCreated;
use Statamic\Events\SubmissionDeleted;
use Statamic\Events\SubmissionSaved;
use Statamic\Events\SubmissionSaving;
use Statamic\Forms\Submission as FileEntry;

class Submission extends FileEntry
{
    protected $model;

    private $id;

    public static function fromModel(Model $model)
    {
        return (new static())
            ->id($model->id)
            ->date($model->created_at)
            ->data($model->data)
            ->model($model);
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.forms.submission_model');
        $timestamp = (new $class)->fromDateTime($this->date());

        $model = $class::findOrNew($this->id());

        return (! empty($model->id)) ? $model->fill([
            'data' => $this->data,
        ]) : $model->fill([
            'data' => $this->data,
            'form' => $this->form->handle(),
            'created_at' => $timestamp,
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

        return $this->date ?? ($this->date = Carbon::now());
    }

    public function save()
    {
        if (SubmissionSaving::dispatch($this) === false) {
            return false;
        }

        $model = $this->toModel();
        $model->save();
        $isNew = $model->wasRecentlyCreated;

        $this->model($model->fresh());

        if ($isNew) {
            SubmissionCreated::dispatch($this);
        }
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
