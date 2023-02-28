<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Entry;

class MigrateEntriesBlueprint extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:migrate-entries-blueprint';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate entries blueprint from data to separate column in the database.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Entry::all()->each->saveQuietly();

        return 0;
    }
}
