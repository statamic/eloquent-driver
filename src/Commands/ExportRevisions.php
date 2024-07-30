<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Eloquent\Revisions\RevisionModel;
use Statamic\Revisions\Revision;
use Statamic\Revisions\WorkingCopy;

class ExportRevisions extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:export-revisions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports eloquent revisions to flat files.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (config('statamic.revisions.enabled')) {
            $this->exportRevisions();
        }

        return 0;
    }

    private function exportRevisions()
    {
        $files = RevisionModel::all();

        $this->withProgressBar($files, function ($model) {
            $class = $model->action != 'working' ? Revision::class : WorkingCopy::class;

            $revision = (new $class)
                ->key($model->key)
                ->action($model->action ?? false)
                ->id($model->created_at->timestamp)
                ->date($model->created_at)
                ->user($model->user ?? false)
                ->message($model->message ?? '')
                ->attributes($model->attributes ?? [])
                ->save();
        });

        $this->newLine();
        $this->info('Revisions exported');
    }
}
