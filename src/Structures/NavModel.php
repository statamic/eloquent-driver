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
}
