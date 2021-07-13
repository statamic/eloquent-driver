<?php

namespace Statamic\Eloquent\Taxonomies;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Arr;

class TermModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'taxonomy_terms';

    protected $casts = [
        'data' => 'json',
    ];

    public function getAttribute($key)
    {
        return Arr::get($this->getAttributeValue('data'), $key, parent::getAttribute($key));
    }
}
