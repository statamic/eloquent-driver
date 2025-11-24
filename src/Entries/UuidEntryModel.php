<?php

namespace Statamic\Eloquent\Entries;

use Statamic\Eloquent\Taxonomies\TermModel;
use Statamic\Support\Str;

class UuidEntryModel extends EntryModel
{
    use EagerLoadsTaxonomies;

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

    public function terms()
    {
        $pivotTable = config('statamic.eloquent-driver.table_prefix', '').'entry_term';

        return $this->belongsToMany(TermModel::class, $pivotTable, 'entry_id', 'term_id')
            ->withPivot(['taxonomy', 'field'])
            ->withTimestamps();
    }
}
