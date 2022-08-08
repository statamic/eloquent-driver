<?php

namespace Statamic\Eloquent\Forms;

use Statamic\Contracts\Forms\Form as Contract;
use Statamic\Eloquent\Forms\FormModel as Model;
use Statamic\Events\FormDeleted;
use Statamic\Events\FormSaved;
use Statamic\Forms\Form as FileEntry;

class Form extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static)
            ->title($model->title)
            ->handle($model->handle)
            ->store($model->settings['store'] ?? null)
            ->email($model->settings['email'] ?? null)
            ->honeypot($model->settings['honeypot'] ?? null)
            ->model($model);
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source)
    {
        $class = app('statamic.eloquent.forms.model');

        $isFileEntry = get_class($source) == FileEntry::class;

        return $class::findOrNew($isFileEntry ? null : $source->model?->id)
            ->fill([
                'title' => $source->title(),
                'handle' => $source->handle(),
                'settings' => [
                    'store' => $source->store(),
                    'email' => $source->email(),
                    'honeypot' => $source->honeypot(),
                ],
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

    public function save()
    {
        $model = $this->toModel();
        $model->save();

        $this->model($model->fresh());

        FormSaved::dispatch($this);
    }

    public function delete()
    {
        $this->submissions()->each->delete();
        $this->model()->delete();

        FormDeleted::dispatch($this);
    }

    public function submissions()
    {
        return $this->model()->submissions()->get()->map(function ($model) {
            $submission = $this->makeSubmission()
                ->id($model->id)
                ->data($model->data);

            $submission
                ->date($model->created_at);

            return $submission;
        });
    }

    public function submission($id)
    {
        return $this->submissions()->filter(function ($submission) use ($id) {
            return $submission->id() == $id;
        })->first();
    }
}
