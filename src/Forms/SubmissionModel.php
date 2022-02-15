<?php

namespace Statamic\Eloquent\Forms;

use Illuminate\Database\Eloquent\Model as Eloquent;

class SubmissionModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'form_submissions';

    protected $casts = [
        'data' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('statamic.eloquent-driver.table_prefix', '').$this->getTable());
    }

    public function form()
    {
        return $this->hasOne(FormModel::class, 'id');
    }
}
