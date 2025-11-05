<?php

namespace Statamic\Eloquent\Revisions;

use Illuminate\Database\Eloquent\Model;
use Statamic\Events\RevisionDeleted;
use Statamic\Events\RevisionSaved;
use Statamic\Events\RevisionSaving;
use Statamic\Revisions\Revision as FileEntry;
use Statamic\Revisions\WorkingCopy;

class Revision extends FileEntry
{
    protected $id;

    protected $key;

    protected $date;

    protected $user;

    protected $userId;

    protected $message;

    protected $action = 'revision';

    protected $attributes = [];

    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static)
            ->key($model->key)
            ->action($model->action ?? null)
            ->id($model->created_at->timestamp)
            ->date($model->created_at)
            ->user($model->user ?? null)
            ->message($model->message ?? null)
            ->attributes($model->attributes ?? [])
            ->model($model);
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.revisions.model');

        return $class::firstOrNew(['key' => $this->key(), 'created_at' => $this->date()])->fill([
            'action' => $this->action(),
            'user' => $this->user()?->id(),
            'message' => with($this->message(), fn ($msg) => $msg == '0' ? '' : $msg),
            'attributes' => $this->attributes(),
            'updated_at' => $this->date(),
        ]);
    }

    public function fromRevisionOrWorkingCopy($item)
    {
        return (new static)
            ->key($item->key())
            ->action($item->isWorkingCopy() ? 'working' : $item->action())
            ->date($item->date())
            ->user($item->user()?->id() ?? false)
            ->message($item->message() ?? null)
            ->attributes($item->attributes() ?? []);
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
        if (RevisionSaving::dispatch($this) === false) {
            return false;
        }

        $this->model->save();

        RevisionSaved::dispatch($this);
    }

    public function delete()
    {
        $this->model->delete();

        RevisionDeleted::dispatch($this);
    }
}
