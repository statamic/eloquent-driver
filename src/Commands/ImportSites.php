<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Eloquent\Sites\Sites as EloquentSites;
use Statamic\Sites\Sites;

class ImportSites extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:import-sites';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file-based sites into the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sites = (new Sites)->config();

        (new EloquentSites)->setSites($sites)->save();

        $this->components->info('Sites imported successfully.');

        return 0;
    }
}
