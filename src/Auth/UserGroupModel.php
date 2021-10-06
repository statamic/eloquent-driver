<?php

namespace Statamic\Eloquent\Auth;

use Illuminate\Database\Eloquent\Model as Eloquent;

class UserGroupModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'groups';

    protected $casts = [
        'roles' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
