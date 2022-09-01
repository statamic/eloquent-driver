<?php

namespace Statamic\Eloquent\Entries;

use Statamic\Support\Str;

class UuidEntryModel extends EntryModel
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($entry) {
            if (empty($entry->{$entry->getKeyName()})) {
                $entry->{$entry->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}
