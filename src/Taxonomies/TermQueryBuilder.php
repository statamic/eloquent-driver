<?php

namespace Statamic\Eloquent\Taxonomies;

use Statamic\Contracts\Taxonomies\Term as TermContract;
use Statamic\Facades\Site;
use Statamic\Query\EloquentQueryBuilder;
use Statamic\Taxonomies\TermCollection;

class TermQueryBuilder extends EloquentQueryBuilder
{
    protected $site = null;

    protected $columns = [
        'id', 'data', 'site', 'slug', 'uri', 'taxonomy', 'created_at', 'updated_at',
    ];

    protected function transform($items, $columns = [])
    {
        $site = $this->site;
        if(!$site) {
            $site = Site::default()->handle();
        }

        return TermCollection::make($items)->map(function ($model) use($site) {
            return app(TermContract::class)::fromModel($model)->in($site);
        });
    }

    protected function column($column)
    {
        if (! in_array($column, $this->columns)) {
            $column = 'data->'.$column;
        }

        return $column;
    }

    public function where($column, $operator = null, $value = null)
    {
        if ($column === 'site') {
            $this->site = $operator;

            return $this;
        }

        return parent::where($column, $operator, $value);
    }
}
