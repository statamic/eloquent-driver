<?php

namespace Statamic\Eloquent\Forms;

use Statamic\Eloquent\Database\BaseModel;

class FormModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'forms';

    protected $casts = [
        'settings' => 'json',
    ];

    public function submissions()
    {
        return $this->hasMany(app('statamic.eloquent.forms.submission_model'), 'form_id');
    }
}
