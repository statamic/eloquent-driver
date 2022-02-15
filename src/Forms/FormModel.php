<?php

namespace Statamic\Eloquent\Forms;

use Illuminate\Database\Eloquent\Model as Eloquent;

class FormModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'forms';

    protected $casts = [
        'settings' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('statamic.eloquent-driver.table_prefix', '').$this->getTable());
    }

    public function submissions()
    {
        return $this->hasMany(SubmissionModel::class, 'form_id');
    }
}
