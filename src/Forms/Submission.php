<?php

namespace Statamic\Eloquent\Forms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Statamic\Events\SubmissionCreated;
use Statamic\Events\SubmissionDeleted;
use Statamic\Events\SubmissionSaved;
use Statamic\Events\SubmissionSaving;
use Statamic\Forms\Submission as FileEntry;

class Submission extends FileEntry
{
    protected $model;

    private $date;
    private $id;

    public static function fromModel(Model $model)
    {
        return (new static())
            ->id($model->id)
            ->date($model->data['date'] ?? Carbon::now())
            ->data(Arr::except($model->data, 'date'))
            ->model($model);
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.form_submissions.model');
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
            if (is_string($date)) {
                $date = Carbon::parse($date);
            }

            $this->date = $date;

            return $this;
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
            $class = app('statamic.eloquent.form_submissions.model');
            $this->model = $class::findOrNew($this->id);
        }

        $this->model->delete();

        SubmissionDeleted::dispatch($this);
    }
}
