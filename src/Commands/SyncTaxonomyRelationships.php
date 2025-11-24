<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Facades\Collection;
use Statamic\Facades\Taxonomy;

class SyncTaxonomyRelationships extends Command
{
    protected $signature = 'eloquent:sync-taxonomy-relationships {--force : Force sync even if relationships exist}';

    protected $description = 'Sync taxonomy relationships from JSON data to pivot table';

    public function handle()
    {
        $this->info('Syncing taxonomy relationships...');

        $entryModel = app('statamic.eloquent.entries.model');
        $termModel = app('statamic.eloquent.terms.model');

        // Check if we should skip if relationships already exist
        if (! $this->option('force') && $entryModel::has('terms')->exists()) {
            if (! $this->confirm('Taxonomy relationships already exist. Continue anyway?')) {
                return;
            }
        }

        $collections = Collection::all();
        $taxonomies = Taxonomy::all();

        $totalEntries = $entryModel::count();
        $processedEntries = 0;

        $this->output->progressStart($totalEntries);

        $entryModel::chunk(100, function ($entries) use ($termModel, $taxonomies, &$processedEntries) {
            foreach ($entries as $entry) {
                $this->syncEntryTaxonomies($entry, $termModel, $taxonomies);
                $processedEntries++;
                $this->output->progressAdvance();
            }
        });

        $this->output->progressFinish();
        $this->info("Synced taxonomy relationships for {$processedEntries} entries.");
    }

    protected function syncEntryTaxonomies($entry, $termModel, $taxonomies)
    {
        $data = $entry->data ?? [];
        $relationships = [];

        foreach ($taxonomies as $taxonomy) {
            $handle = $taxonomy->handle();

            if (! isset($data[$handle])) {
                continue;
            }

            $termSlugs = is_array($data[$handle]) ? $data[$handle] : [$data[$handle]];
            $termSlugs = array_unique(array_filter($termSlugs)); // Remove duplicates and empty values

            foreach ($termSlugs as $slug) {
                $term = $termModel::where('taxonomy', $handle)
                    ->where('slug', $slug)
                    ->first();

                if ($term) {
                    $key = "{$entry->id}-{$term->id}-{$handle}";
                    $relationships[$key] = [
                        'entry_id' => $entry->id,
                        'term_id' => $term->id,
                        'taxonomy' => $handle,
                        'field' => $handle,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        if (! empty($relationships)) {
            $pivotTable = config('statamic.eloquent-driver.table_prefix', '').'entry_term';

            // Remove existing relationships for this entry
            \DB::table($pivotTable)->where('entry_id', $entry->id)->delete();

            // Insert new relationships (values only, keys were just for deduplication)
            \DB::table($pivotTable)->insert(array_values($relationships));
        }
    }
}
