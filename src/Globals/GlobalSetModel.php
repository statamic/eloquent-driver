<?php

namespace Statamic\Eloquent\Globals;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Arr;

class GlobalSetModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'global_sets';

    protected $casts = [
        'localizations' => 'json',
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
