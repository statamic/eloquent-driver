<?php

namespace Statamic\Eloquent\Entries;

use Illuminate\Database\Eloquent\Model as Eloquent;

class EntryModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'entries';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'date' => 'datetime',
        'data' => 'json',
        'published' => 'bool',
    ];

    public function origin()
    {
        return $this->belongsTo(self::class);
    }
}
