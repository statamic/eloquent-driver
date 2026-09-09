<?php

namespace Statamic\Eloquent\Forms;

use Statamic\Contracts\Forms\SubmissionQueryBuilder as BuilderContract;
use Statamic\Data\DataCollection;
use Statamic\Facades\Blink;
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

        if ($column == 'datestamp' || $column == 'date') {
            $column = 'created_at';
        }

        if (! in_array($column, self::COLUMNS)) {
            if (! Str::startsWith($column, 'data->')) {
                $column = 'data->'.$column;
            }
        }

        return $column;
    }

    public function whereStatus(string $status)
    {
        return match ($status) {
            'any' => $this,
            'finalized' => $this->whereNotTrue('partial')->whereNotTrue('spam'),
            'partial' => $this->where('partial', true)->whereNotTrue('spam'),
            'spam' => $this->where('spam', true),
            default => throw new \Exception("Invalid status [$status]"),
        };
    }

    private function whereNotTrue(string $column)
    {
        return $this->where(
            fn ($query) => $query->whereNull($column)->orWhere($column, '!=', true)
        );
    }

    protected function transform($items, $columns = [])
    {
        return DataCollection::make($items)->map(function ($model) {
            return Submission::fromModel($model)
                ->form(Blink::once("eloquent-forms-{$model->form}", fn () => Form::find($model->form)));
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
