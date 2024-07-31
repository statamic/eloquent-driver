<?php

namespace Statamic\Eloquent\Forms;

use Statamic\Contracts\Forms\SubmissionQueryBuilder as BuilderContract;
use Statamic\Facades\Form;
use Statamic\Query\EloquentQueryBuilder;
use Statamic\Support\Str;

class SubmissionQueryBuilder extends EloquentQueryBuilder implements BuilderContract
{
    const COLUMNS = [
        'id', 'form', 'created_at', 'updated_at',
    ];

    protected function column($column)
    {
        if (! is_string($column)) {
            return $column;
        }

        if ($column == 'datestamp') {
            $column = 'created_at';
        }

        if (! in_array($column, self::COLUMNS)) {
            if (! Str::startsWith($column, 'data->')) {
                $column = 'data->'.$column;
            }
        }

        return $column;
    }

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
