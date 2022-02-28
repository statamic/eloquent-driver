<?php

namespace Statamic\Eloquent\Revisions;

use Statamic\Contracts\Revisions\Revision as Contract;
use Statamic\Revisions\Revision as FileEntry;
use Statamic\Eloquent\Revisions\RevisionModel as Model;
use Statamic\Events\RevisionDeleted;
use Statamic\Events\RevisionSaved;

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
            ->action($model->action ?? false)
            ->id($model->created_at->timestamp)
            ->date($model->created_at)
            ->user($model->user ?? false)
            ->message($model->message ?? '')
            ->attributes($model->attributes ?? [])
            ->model($model);
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.revisions.model');

        return $class::findOrNew($this->model?->id)->fill([
            'key' => $this->key(),
            'action' => $this->action(),
            'user' => $this->user()->id(),
            'message' => $this->message(),
            'attributes' => $this->attributes()->except('id'),
        ]);
    }
    
    public function fromWorkingCopy($workingCopy)
    {
        return (new static)
            ->key($workingCopy->key())
            ->action($workingCopy->action() ?? false)
            ->date($workingCopy->date())
            ->user($workingCopy->user()->id() ?? false)
            ->message($workingCopy->message() ?? '')
            ->attributes($workingCopy->attributes() ?? []);      
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
        $this->model->save();

        RevisionSaved::dispatch($this);
    }

    public function delete()
    {
        $this->model->delete();

        RevisionDeleted::dispatch($this);
    }
}
