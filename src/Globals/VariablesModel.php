<?php

namespace Statamic\Eloquent\Globals;

use Illuminate\Support\Arr;
use Statamic\Eloquent\Database\BaseModel;

class VariablesModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'global_set_variables';

    protected $casts = [];

    public function getAttribute($key)
    {
        return Arr::get($this->getAttributeValue('data'), $key, parent::getAttribute($key));
    }

    public function toArray()
    {
        $attrs = parent::toArray();
        // Ensure that origin is properly serialized as locale string.
        if (isset($attrs['origin']) && $attrs['origin'] instanceof Variables) {
            $attrs['origin'] = $attrs['origin']->locale();
        }

        return $attrs;
    }
}
