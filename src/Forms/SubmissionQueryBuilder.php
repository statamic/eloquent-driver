<?php

namespace Statamic\Eloquent\Forms;

use Statamic\Contracts\Forms\SubmissionQueryBuilder as BuilderContract;
use Statamic\Eloquent\Forms\Submission;
use Statamic\Facades\Form;
use Statamic\Query\EloquentQueryBuilder;

class SubmissionQueryBuilder extends EloquentQueryBuilder implements BuilderContract
{
    const COLUMNS = [
        'id', 'form', 'created_at', 'updated_at',
    ];

    protected function transform($items, $columns = [])
    {
        return $items->map(function ($model) {
            return Submission::fromModel($model)
                ->form(Form::find($model->form));
        });
    }

    public function with($relations, $callback = null)
    {
        return $this;
    }

    public function first()
    {
        return $this->get()->first();

    }
}
