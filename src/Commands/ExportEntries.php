<?php

namespace Statamic\Eloquent\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Entries\Entry as StacheEntry;
use Statamic\Facades\Entry;
use Statamic\Stache\Repositories\CollectionRepository;
use Statamic\Stache\Repositories\EntryRepository;
use Statamic\Statamic;

class ExportEntries extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:export-entries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports eloquent entries to flat files.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->usingDefaultRepositories(function () {
            $this->importEntries();
        });

        return 0;
    }

    private function usingDefaultRepositories(Closure $callback)
    {
        Facade::clearResolvedInstance(EntryRepositoryContract::class);
        Facade::clearResolvedInstance(CollectionRepositoryContract::class);

        Statamic::repository(EntryRepositoryContract::class, EntryRepository::class);
        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);

        app()->bind(EntryContract::class, StacheEntry::class);

        $callback();
    }

    private function importEntries()
    {
        $entries = EntryModel::all();

        $entriesWithOrigin = $entries->filter(function ($model) {
            return (bool) $model->origin_id;
        });

        $entriesWithoutOrigin = $entries->filter(function ($model) {
            return ! $model->origin_id;
        });

        if ($entriesWithOrigin->count() > 0) {
            $this->info('Importing origin entries');
        }

        $this->withProgressBar($entriesWithoutOrigin, function ($model) {
            $entry = Entry::make()
                ->locale($model->site)
                ->slug($model->slug)
                ->collection($model->collection)
                ->data($model->data)
                ->blueprint($model->data['blueprint'] ?? null)
                ->published($model->published);

            if ($model->date && $entry->collection()->dated()) {
                $entry->date($model->date);
            }

            if (config('statamic.system.track_last_update')) {
                $entry->set('updated_at', $model->updated_at ?? $model->created_at);
            }

            $entry->save();
        });

        if ($entriesWithOrigin->count() > 0) {
            $this->newLine();
            $this->info('Importing localized entries');

            $this->withProgressBar($entriesWithOrigin, function ($model) {
                $entry = Entry::make()
                    ->origin($model->origin_id)
                    ->locale($model->site)
                    ->slug($model->slug)
                    ->collection($model->collection)
                    ->data($model->data)
                    ->blueprint($model->data['blueprint'] ?? null)
                    ->published($model->published);

                if ($model->date && $entry->collection()->dated()) {
                    $entry->date($model->date);
                }

                if (config('statamic.system.track_last_update')) {
                    $entry->set('updated_at', $model->updated_at ?? $model->created_at);
                }

                $entry->save();
            });
        }

        $this->newLine();
        $this->info('Entries exported');
    }
}
