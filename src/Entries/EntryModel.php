<?php

namespace Statamic\Eloquent\Entries;

use Illuminate\Database\Eloquent\Model as Eloquent;

class EntryModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'entries';

    protected $casts = [
        'date' => 'datetime',
        'data' => 'json',
        'published' => 'bool',
    ];

    public function origin()
    {
        return $this->belongsTo(self::class);
    }

    public static function generateId()
    {
        return null;
    }
}
