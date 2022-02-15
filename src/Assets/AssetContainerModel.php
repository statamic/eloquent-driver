<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Arr;

class AssetContainerModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'asset_containers';

    protected $casts = [
        'settings' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('statamic.eloquent-driver.table_prefix', '').$this->getTable());
    }

    public function getAttribute($key)
    {
        return Arr::get($this->getAttributeValue('settings'), $key, parent::getAttribute($key));
    }
}
