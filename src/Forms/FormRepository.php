<?php

namespace Statamic\Eloquent\Forms;

use Statamic\Contracts\Forms\Form as FormContract;
use Statamic\Facades\Blink;
use Statamic\Forms\FormRepository as StacheRepository;

class FormRepository extends StacheRepository
{
    public function find($handle)
    {
        return Blink::once("eloquent-forms-{$handle}", function () use ($handle) {
            $model = app('statamic.eloquent.forms.model')::whereHandle($handle)->first();

            if (! $model) {
                return;
            }

            return app(FormContract::class)->fromModel($model);
        });
    }

    public function all()
    {
        return Blink::once('eloquent-forms', function () {
            return app('statamic.eloquent.forms.model')::all()
                ->map(function ($form) {
                    return app(FormContract::class)::fromModel($form);
                });
        });
    }

    public function make($handle = null)
    {
        $form = app(FormContract::class);

        if ($handle) {
            $form->handle($handle);
        }

        return $form;
    }

    public static function bindings(): array
    {
        return [
            FormContract::class => Form::class,
        ];
    }
}
