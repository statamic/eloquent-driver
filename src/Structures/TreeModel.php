<?php

namespace Statamic\Eloquent\Structures;

use Illuminate\Database\Eloquent\Model as Eloquent;

class TreeModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'trees';

    protected $casts = [
        'tree' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('statamic.eloquent-driver.table_prefix', '').$this->getTable());
    }
}
