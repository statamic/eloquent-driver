<?php

namespace Statamic\Eloquent\Forms;

use Statamic\Contracts\Forms\SubmissionQueryBuilder as BuilderContract;
use Statamic\Data\DataCollection;
use Statamic\Eloquent\CollatesOrderBy;
use Statamic\Facades\Blink;
use Statamic\Facades\Form;
use Statamic\Facades\Site;
use Statamic\Query\EloquentQueryBuilder;
use Statamic\Sites\Site as SiteObject;
use Statamic\Support\Str;

class SubmissionQueryBuilder extends EloquentQueryBuilder implements BuilderContract
{
    use CollatesOrderBy;

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

        if ($column == 'site') {
            $column = '_site';
        }

        if (! in_array($column, self::COLUMNS)) {
            if (! Str::startsWith($column, 'data->')) {
                $column = 'data->'.$column;
            }
        }

        return $column;
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column === 'site') {
            if (func_num_args() === 2 || (is_null($value) && ! in_array($operator, ['=', '!=', '<>'], true))) {
                [$value, $operator] = [$operator, '='];
            }

            if ($operator === '=' && ! is_null($value)) {
                return $this->whereSites([$value], $boolean);
            }
        }

        return parent::where(...func_get_args());
    }

    public function whereIn($column, $values, $boolean = 'and')
    {
        if ($column === 'site') {
            return $this->whereSites($values, $boolean);
        }

        if ($column === 'id') {
            $values = collect($values)->filter(fn ($id) => is_numeric($id))->values()->all();
        }

        return parent::whereIn($column, $values, $boolean);
    }

    private function whereSites($sites, string $boolean)
    {
        $handles = collect($sites)
            ->map(fn ($site) => $site instanceof SiteObject ? $site->handle() : $site)
            ->all();

        return $this->whereNested(function ($query) use ($handles) {
            $query->whereIn('_site', $handles);

            if (in_array(Site::default()->handle(), $handles)) {
                $query->orWhereNull('_site');
            }
        }, $boolean);
    }

    public function whereStatus(string $status)
    {
        return match ($status) {
            'any' => $this,
            'finalized' => $this->whereNotTrue('_partial')->whereNull('_marked_spam_at'),
            'partial' => $this->where('_partial', true)->whereNull('_marked_spam_at'),
            'spam' => $this->whereNotNull('_marked_spam_at'),
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
        return $this->limit(1)->get()->first();
    }
}
