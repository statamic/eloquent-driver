<?php

namespace Statamic\Eloquent\Entries;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Statamic\Eloquent\Database\BaseModel;

class EntryModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'entries';

    protected function casts(): array
    {
        return [
            'data'      => 'json',
            'published' => 'boolean',
        ];
    }

    public function date(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return Carbon::parse($value, 'UTC');
            },
            set: function ($value) {
                if (! $value instanceof Carbon) {
                    $value = Carbon::parse($value, 'UTC');
                }

                if ($value->tzName !== 'UTC') {
                    $value = $value->utc();
                }

                return $value->format('Y-m-d H:i:s');
            },
        );
    }

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
