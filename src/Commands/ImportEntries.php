<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Eloquent\Entries\Entry as EloquentEntry;
use Statamic\Facades\Entry;
use Statamic\Stache\Repositories\CollectionRepository;
use Statamic\Stache\Repositories\EntryRepository;
use Statamic\Statamic;

class ImportEntries extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:import-entries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file-based entries into the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->useDefaultRepositories();

        $this->importEntries();

        return 0;
    }

    private function useDefaultRepositories(): void
    {
        Facade::clearResolvedInstance(EntryRepositoryContract::class);
        Facade::clearResolvedInstance(CollectionRepositoryContract::class);

        Statamic::repository(EntryRepositoryContract::class, EntryRepository::class);
        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);

        app()->bind(EntryContract::class, app('statamic.eloquent.entries.entry'));
    }

    private function importEntries(): void
    {
        $entries = Entry::all();

        $entriesWithOrigin = $entries->filter->hasOrigin();
        $entriesWithoutOrigin = $entries->filter(function ($entry) {
            return ! $entry->hasOrigin();
        });

        if ($entriesWithOrigin->count() > 0) {
            $this->components->info('Importing origin entries...');
        }

        $this->withProgressBar($entriesWithoutOrigin, function ($entry) {
            $lastModified = $entry->fileLastModified();

            $entry = EloquentEntry::makeModelFromContract($entry)
                ->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])
                ->save();
        });

        if ($entriesWithOrigin->count() > 0) {
            $this->components->info('Importing localized entries...');

            $this->withProgressBar($entriesWithOrigin, function ($entry) {
                $lastModified = $entry->fileLastModified();

                EloquentEntry::makeModelFromContract($entry)
                    ->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])
                    ->save();
            });
        }

        $this->components->info('Entries imported successfully.');
    }
}
