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
        return $this->belongsTo(static::class);
    }

    public function getAttribute($key)
    {
        // Because the import script was importing `updated_at` into the
        // json data column, we will explicitly reference other SQL
        // columns first to prevent errors with that bad data.
        if (in_array($key, EntryQueryBuilder::COLUMNS)) {
            return parent::getAttribute($key);
        }

        return Arr::get($this->getAttributeValue('data'), $key, parent::getAttribute($key));
    }
}
