<?php

namespace Statamic\Eloquent\Auth;

use Illuminate\Database\Eloquent\Model as Eloquent;

class RoleModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'roles';

    protected $casts = [
        'permissions' => 'json',
        'preferences' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
