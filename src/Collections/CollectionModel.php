<?php

namespace Statamic\Eloquent\Collections;

use Illuminate\Database\Eloquent\Model as Eloquent;

class CollectionModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'collections';

    protected $casts = [
        'settings' => 'json',
        'settings.routes' => 'array',
        'settings.inject' => 'array',
        'settings.taxonomies' => 'array',
        'settings.structure' => 'array',
        'settings.sites' => 'array',
        'settings.revisions' => 'boolean',
        'settings.dated' => 'boolean',
        'settings.default_publish_state' => 'boolean',
        'settings.ampable' => 'boolean',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('statamic.eloquent-driver.table_prefix', '').$this->getTable());
    }
}
