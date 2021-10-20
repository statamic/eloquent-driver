<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Eloquent\Entries\EntryQueryBuilder;
use Statamic\Eloquent\Entries\UuidEntryModel;
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
    }

    private function importEntries()
    {
        $entries = Entry::all();
        $bar = $this->output->createProgressBar($entries->count());

        $entries->each(function ($entry) use ($bar) {
            $this->toModel($entry)->save();
            $bar->advance();
        });

        $bar->finish();
        $this->line('');
        $this->info('Entries imported');
    }

    private function toModel($entry)
    {
        return new UuidEntryModel([
            'id' => $entry->id(),
            'origin_id' => optional($entry->origin())->id(),
            'site' => $entry->locale(),
            'slug' => $entry->slug(),
            'uri' => $entry->uri(),
            'date' => $entry->hasDate() ? $entry->date() : null,
            'collection' => $entry->collectionHandle(),
            'data' => $entry->data()->except(EntryQueryBuilder::COLUMNS),
            'published' => $entry->published(),
            'status' => $entry->status(),
            'created_at' => $entry->lastModified(),
            'updated_at' => $entry->lastModified(),
        ]);
    }
}
