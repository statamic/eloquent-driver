<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Database\Eloquent\Model;

class AssetContainerModel extends Model
{
    protected $guarded = [];

    protected $table = 'asset_containers';

    protected $casts = [
        'data' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
