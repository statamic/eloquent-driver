<?php

namespace Statamic\Eloquent\Assets;

use Statamic\Eloquent\Database\BaseModel;

class AssetModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'assets_meta';

    protected $casts = [
        'data' => 'json',
        'meta' => 'json',
    ];
}
