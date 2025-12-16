<?php

namespace Statamic\Eloquent\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
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
            $this->exportEntries();
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

    private function exportEntries()
    {
        $entries = app('statamic.eloquent.entries.model')::all();

        $entriesWithOrigin = $entries->filter(function ($model) {
            return (bool) $model->origin_id;
        });

        $entriesWithoutOrigin = $entries->filter(function ($model) {
            return ! $model->origin_id;
        });

        if ($entriesWithoutOrigin->count() > 0) {
            $this->info('Exporting origin entries');
        }

        $this->withProgressBar($entriesWithoutOrigin, function ($model) {
            $entry = Entry::make()
                ->collection($model->collection)
                ->locale($model->site)
                ->slug($model->slug)
                ->data($model->data)
                ->blueprint($model->blueprint)
                ->template($model->data['template'] ?? null)
                ->published($model->published);

            if ($model->getKeyType() == 'string' || in_array(HasUuids::class, class_uses_recursive($model))) {
                $entry->id($model->getKey());
            }

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
            $this->info('Exporting localized entries');

            $this->withProgressBar($entriesWithOrigin, function ($model) {

                $entry = Entry::make()
                    ->collection($model->collection)
                    ->origin($model->origin_id)
                    ->locale($model->site)
                    ->slug($model->slug)
                    ->data($model->data)
                    ->blueprint($model->data['blueprint'] ?? null)
                    ->template($model->data['template'] ?? null)
                    ->published($model->published);

                if ($model->getKeyType() == 'string' || in_array(HasUuids::class, class_uses_recursive($model))) {
                    $entry->id($model->getKey());
                }

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
