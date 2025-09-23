<?php

namespace Statamic\Eloquent\AddonSettings;

use Statamic\Eloquent\Database\BaseModel;

class AddonSettingsModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'addon_settings';

    protected $primaryKey = 'addon';

    protected $keyType = 'string';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }
}
