<?php

namespace Statamic\Eloquent\Taxonomies;

use Illuminate\Support\Arr;
use Statamic\Eloquent\Database\BaseModel;

class TermModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'taxonomy_terms';

    protected function casts(): array
    {
        return [
            'data' => 'json',
        ];
    }

    public function getAttribute($key)
    {
        return Arr::get($this->getAttributeValue('data'), $key, parent::getAttribute($key));
    }
}
