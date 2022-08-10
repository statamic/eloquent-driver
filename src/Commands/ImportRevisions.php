<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Statamic\Console\RunsInPlease;
use Statamic\Eloquent\Revisions\Revision;
use Statamic\Facades\YAML;

class ImportRevisions extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:import-revisions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file based revisions into the database.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (config('statamic.revisions.enabled')) {
            $this->importRevisions();
        }

        return 0;
    }

    private function importRevisions()
    {
        $files = File::allFiles(config('statamic.revisions.path'));

        $this->withProgressBar($files, function ($file) {
            $yml = YAML::file($file->getPathname())->parse();
            $revision = (new Revision())
                ->key($file->getRelativePath())
                ->action($yml['action'] ?? false)
                ->date(Carbon::parse($yml['date']))
                ->user($yml['user'] ?? false)
                ->message($yml['message'] ?? '')
                ->attributes($yml['attributes'] ?? []);
            if ($file->getBasename('.yaml') === 'working') {
                $revision->action('working');
            }

            $revision->toModel()->save();
        });

        $this->line('');
        $this->info('Revisions imported');
    }
}
