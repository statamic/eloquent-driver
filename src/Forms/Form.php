<?php

namespace Statamic\Eloquent\Forms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Statamic\Contracts\Forms\Form as Contract;
use Statamic\Events\FormCreated;
use Statamic\Events\FormCreating;
use Statamic\Events\FormDeleted;
use Statamic\Events\FormDeleting;
use Statamic\Events\FormSaved;
use Statamic\Events\FormSaving;
use Statamic\Facades\Blink;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Form as FormFacade;
use Statamic\Forms\Form as FileEntry;

class Form extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        $settings = collect($model->settings);

        $form = (new static)
            ->title($model->title)
            ->handle($model->handle)
            ->store($settings->get('store'))
            ->charts($settings->get('charts'))
            ->honeypot($settings->get('honeypot'))
            ->connections($settings->get('connections'))
            ->data($settings->get('data') ?? [])
            ->model($model);

        if (! is_null($emails = Arr::get($settings, 'connections.email', $settings->get('email')))) {
            $form->email($emails);
        }

        if ($fields = $settings->get('fields')) {
            $form->formFields($fields);
        }

        return $form;
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source)
    {
        $class = app('statamic.eloquent.forms.model');

        return $class::firstOrNew(['handle' => $source->handle()])->fill([
            'title'    => $source->title() ?? $source->handle(),
            'settings' => [
                'fields'      => $source->formFields()->contents(),
                'charts'      => $source->charts(),
                'honeypot'    => $source->honeypot(),
                'connections' => $source->connections()->all(),
                'store'       => $source->store(),
                'data'        => $source->data()->filter(fn ($v) => $v !== null),
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
        $isNew = is_null(FormFacade::find($this->handle()));

        $withEvents = $this->withEvents;
        $this->withEvents = true;

        $afterSaveCallbacks = $this->afterSaveCallbacks;
        $this->afterSaveCallbacks = [];

        if ($withEvents) {
            if ($isNew && FormCreating::dispatch($this) === false) {
                return false;
            }

            if (FormSaving::dispatch($this) === false) {
                return false;
            }
        }

        $model = $this->toModel();
        $model->save();

        $this->model($model->fresh());

        Blink::forget("eloquent-forms-{$this->handle()}");
        Blink::forget('eloquent-forms');

        if ($blueprint = Blueprint::find("forms.{$this->handle()}")) {
            $blueprint->delete();
        }

        foreach ($afterSaveCallbacks as $callback) {
            $callback($this);
        }

        if ($withEvents) {
            if ($isNew) {
                FormCreated::dispatch($this);
            }

            FormSaved::dispatch($this);
        }
    }

    public function delete()
    {
        $withEvents = $this->withEvents;
        $this->withEvents = true;

        if ($withEvents && FormDeleting::dispatch($this) === false) {
            return false;
        }

        $this->submissions()->each->delete();
        $this->model()->delete();

        Blink::forget("eloquent-forms-{$this->handle()}");
        Blink::forget('eloquent-forms');

        if ($withEvents) {
            FormDeleted::dispatch($this);
        }

        return true;
    }
}
