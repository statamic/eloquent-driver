<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Support\Arr;
use Statamic\Eloquent\Database\BaseModel;

class AssetContainerModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'asset_containers';

    protected function casts(): array
    {
        return [
            'settings' => 'json',
        ];
    }

    public function getAttribute($key)
    {
        return Arr::get($this->getAttributeValue('settings'), $key, parent::getAttribute($key));
    }
}
