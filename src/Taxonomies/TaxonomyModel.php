<?php

namespace Statamic\Eloquent\Taxonomies;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Arr;

class TaxonomyModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'taxonomies';

    protected $casts = [
        'sites' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('statamic.eloquent-driver.table_prefix', '').$this->getTable());
    }

    public function getAttribute($key)
    {
        return Arr::get($this->getAttributeValue('data'), $key, parent::getAttribute($key));
    }
}
