<?php

namespace Statamic\Eloquent\Forms;

use Statamic\Eloquent\Database\BaseModel;

class SubmissionModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'form_submissions';

    protected $casts = [
        'data' => 'json',
    ];

    public function form()
    {
        return $this->hasOne(FormModel::class, 'id');
    }
}
