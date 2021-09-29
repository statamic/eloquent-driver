<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Database\Eloquent\Model;

class AssetModel extends Model
{
    protected $guarded = [];

    protected $table = 'asset_meta';

    protected $casts = [
        'data' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
