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
    protected $description = 'Imports file-based revisions into the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! config('statamic.revisions.enabled')) {
            $this->components->error('This import can only be run when revisions are enabled.');

            return 1;
        }

        $this->importRevisions();

        return 0;
    }

    private function importRevisions(): void
    {
        $this->withProgressBar(File::allFiles(config('statamic.revisions.path')), function ($file) {
            $yaml = YAML::file($file->getPathname())->parse();

            $revision = (new Revision)
                ->key($file->getRelativePath())
                ->action($yaml['action'] ?? false)
                ->date(Carbon::parse($yaml['date']))
                ->user($yaml['user'] ?? false)
                ->message($yaml['message'] ?? '')
                ->attributes($yaml['attributes'] ?? []);

            if ($file->getBasename('.yaml') === 'working') {
                $revision->action('working');
            }

            $revision->toModel()->save();
        });

        $this->components->info('Revisions imported successfully.');
    }
}
