<?php

use Illuminate\Support\Facades\DB;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    public function up()
    {
        $table = $this->prefix('taxonomy_terms');

        DB::table($table)
            ->whereNull('origin')
            ->get()
            ->each(function ($term) use ($table) {
                $data = json_decode($term->data, true);
                $localizations = $data['localizations'] ?? [];

                if (empty($localizations)) {
                    return;
                }

                unset($data['localizations']);

                DB::table($table)
                    ->where('id', $term->id)
                    ->update(['data' => json_encode($data)]);

                foreach ($localizations as $locale => $localeData) {
                    if ($locale === $term->site) {
                        continue;
                    }

                    DB::table($table)->insertOrIgnore([
                        'site'       => $locale,
                        'origin'     => $term->id,
                        'slug'       => $term->slug,
                        'uri'        => null,
                        'taxonomy'   => $term->taxonomy,
                        'data'       => json_encode($localeData),
                        'created_at' => $term->created_at,
                        'updated_at' => $term->updated_at,
                    ]);
                }
            });
    }

    public function down()
    {
        // Non-reversible: restoring JSON localizations from rows would require
        // re-implementing the merge logic, and any rows added after this migration
        // would be lost. Use a database backup to roll back.
    }
};
