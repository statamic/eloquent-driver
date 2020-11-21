<?php

namespace Statamic\Eloquent\Entries;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Arr;

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

    public function getAttribute($key)
    {
        return Arr::get($this->getAttributeValue('data'), $key, parent::getAttribute($key));
    }
}
