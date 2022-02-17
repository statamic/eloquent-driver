<?php

namespace Statamic\Eloquent\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class FieldsetModel extends Model
{
    protected $guarded = [];

    protected $table = 'fieldsets';

    protected $casts = [
        'data' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
