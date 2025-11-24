<?php

namespace Statamic\Eloquent\Entries;

use Illuminate\Support\Arr;
use Statamic\Eloquent\Database\BaseModel;
use Statamic\Eloquent\Taxonomies\TermModel;

class EntryModel extends BaseModel
{
    use EagerLoadsTaxonomies;

    protected $guarded = [];

    protected $table = 'entries';

    protected $casts = [
        'date'      => 'datetime',
        'data'      => 'json',
        'published' => 'boolean',
    ];

    public function author()
    {
        return $this->belongsTo(\App\Models\User::class, 'data->author');
    }

    public function origin()
    {
        return $this->belongsTo(static::class);
    }

    public function parent()
    {
        return $this->belongsTo(static::class, 'data->parent');
    }

    public function terms()
    {
        $pivotTable = config('statamic.eloquent-driver.table_prefix', '') . 'entry_term';
        
        return $this->belongsToMany(TermModel::class, $pivotTable, 'entry_id', 'term_id')
            ->withPivot(['taxonomy', 'field'])
            ->withTimestamps();
    }

    public function termsForField($field)
    {
        return $this->terms()->wherePivot('field', $field);
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
