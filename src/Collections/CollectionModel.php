<?php

namespace Statamic\Eloquent\Collections;

use Statamic\Eloquent\Database\BaseModel;

class CollectionModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'collections';

    protected $casts = [
        'settings'                       => 'json',
        'settings.routes'                => 'array',
        'settings.inject'                => 'array',
        'settings.taxonomies'            => 'array',
        'settings.structure'             => 'array',
        'settings.sites'                 => 'array',
        'settings.revisions'             => 'boolean',
        'settings.dated'                 => 'boolean',
        'settings.default_publish_state' => 'boolean',
    ];
}
