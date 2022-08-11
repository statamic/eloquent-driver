<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
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
    protected $description = 'Imports file based entries into the database.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->useDefaultRepositories();

        $this->importEntries();

        return 0;
    }

    private function useDefaultRepositories()
    {
        Statamic::repository(EntryRepositoryContract::class, EntryRepository::class);
        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);

        app()->bind(EntryContract::class, app('statamic.eloquent.entries.entry'));
    }

    private function importEntries()
    {
        $entries = Entry::all();

        $this->withProgressBar($entries, function ($entry) {
            $lastModified = $entry->fileLastModified();
            $entry->toModel()->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])->save();
        });

        $this->newLine();
        $this->info('Entries imported');
    }
}
