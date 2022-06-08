<?php

namespace Statamic\Eloquent\Assets;

use Statamic\Eloquent\Database\BaseModel;

class AssetModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'assets_meta';

    protected $casts = [
        'data' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
