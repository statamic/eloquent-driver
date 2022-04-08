<?php

namespace Statamic\Eloquent\Taxonomies;

use Illuminate\Support\Str;
use Statamic\Contracts\Taxonomies\Term as TermContract;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Query\EloquentQueryBuilder;
use Statamic\Taxonomies\TermCollection;

class TermQueryBuilder extends EloquentQueryBuilder
{
    protected $collections = [];
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

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {        
        if ($column === 'site') {
            $this->site = $operator;

            return $this;
        }
        
        if (in_array($column, ['taxonomy', 'taxonomies'])) {
            $column = 'taxonomy';
        }

        if (in_array($column, ['collection', 'collections'])) {
            if (is_null($value)) {
                return $this;
            }
            
            $this->collections[] = array_merge($this->collections, $values);
            $column = 'collection';
        }
        
        if (in_array($column, ['id'])) {
            $column = 'slug';
            $value = Str::after($value, '::');
        }
         
        return parent::where($column, $operator, $value, $boolean);
    }
    
    public function whereIn($column, $values, $boolean = 'and')
    {
        if (in_array($column, ['taxonomy', 'taxonomies'])) {
            $column = 'taxonomy';
        }

        if (in_array($column, ['collection', 'collections'])) {
            if (is_null($value)) {
                return $this;
            }
            
            $this->collections[] = array_merge($this->collections, $values);
            $column = 'collection';
        }
        
        if (in_array($column, ['id'])) {
            $column = 'slug';
            $values = collect($values)
                ->map(function ($value) {
                    return Str::after($value, '::');
                })
                ->all();   
        }
        
        return parent::whereIn($column, $values, $boolean);
    }
    
    public function get($columns = ['*'])
    {
        $items = parent::get($columns);

        // If a single collection has been queried, we'll supply it to the terms so
        // things like URLs will be scoped to the collection. We can't do it when
        // multiple collections are queried because it would be ambiguous.
        if ($this->collections && count($this->collections) == 1) {
            $items->each->collection(Collection::findByHandle($this->collections[0]));
        }
        
        return $items;
    }
}
