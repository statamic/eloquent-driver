<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Database\Eloquent\Model;
use Statamic\Eloquent\Database\BaseModel;
use Statamic\Support\Str;

class AssetModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'assets_meta';

    protected $casts = [
        'data' => 'json',
        'meta' => 'json',
    ];
}
