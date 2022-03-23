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
        return $this->hasMany(SubmissionModel::class, 'form_id');
    }
}
