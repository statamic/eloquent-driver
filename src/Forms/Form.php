<?php

namespace Statamic\Eloquent\Forms;

use Illuminate\Database\Eloquent\Model;
use Statamic\Contracts\Forms\Form as Contract;
use Statamic\Events\FormDeleted;
use Statamic\Events\FormSaved;
use Statamic\Facades\Blink;
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
            ->data($model->settings['data'] ?? [])
            ->model($model);
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source)
    {
        $class = app('statamic.eloquent.forms.model');

        return $class::firstOrNew(['handle' => $source->handle()])->fill([
            'title'    => $source->title(),
            'settings' => [
                'store'    => $source->store(),
                'email'    => $source->email(),
                'honeypot' => $source->honeypot(),
                'data' => $source->data()->filter(fn ($v) => $v !== null),
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

        Blink::forget("eloquent-forms-{$this->handle()}");
        Blink::forget('eloquent-forms');

        FormSaved::dispatch($this);
    }

    public function delete()
    {
        $this->submissions()->each->delete();
        $this->model()->delete();

        Blink::forget("eloquent-forms-{$this->handle()}");
        Blink::forget('eloquent-forms');

        FormDeleted::dispatch($this);
    }
}
