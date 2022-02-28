<?php

namespace Statamic\Eloquent\Revisions;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Arr;

class RevisionModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'revisions';

    protected $casts = [
        'attributes' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('statamic.eloquent-driver.table_prefix', '').$this->getTable());
    }
}
