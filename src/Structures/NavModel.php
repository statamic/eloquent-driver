<?php

namespace Statamic\Eloquent\Structures;

use Illuminate\Database\Eloquent\Model as Eloquent;

class NavModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'navigations';

    protected $casts = [
        'collections' => 'json',
        'expectsRoot' => 'boolean',
        'maxDepth' => 'integer',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('statamic.eloquent-driver.table_prefix', '').$this->getTable());
    }
}
