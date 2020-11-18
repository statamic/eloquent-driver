<?php

namespace Statamic\Eloquent\Entries;

use Statamic\Support\Str;

class UuidEntryModel extends EntryModel
{
    public $incrementing = false;
    protected $keyType = 'string';

    public static function generateId()
    {
        return (string) Str::uuid();
    }
}
