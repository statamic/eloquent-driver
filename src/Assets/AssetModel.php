<?php

namespace Statamic\Eloquent\Assets;

use Statamic\Eloquent\Database\BaseModel;

class AssetModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'assets_meta';

    protected function casts(): array
    {
        return [
            'data' => 'json',
            'meta' => 'json',
            'duration' => 'float',
            'height' => 'integer',
            'last_modified' => 'integer',
            'size' => 'integer',
            'width' => 'integer',
        ];
    }
}
