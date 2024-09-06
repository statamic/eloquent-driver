<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Eloquent\Sites\SiteModel;
use Statamic\Sites\Sites;

class ExportSites extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:export-sites';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports eloquent sites to flat files.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sites = SiteModel::all()
            ->mapWithKeys(function ($model) {
                return [
                    $model->handle => collect([
                        'name' => $model->name,
                        'lang' => $model->lang ?? null,
                        'locale' => $model->locale,
                        'url' => $model->url,
                        'attributes' => $model->attributes ?? [],
                    ])->filter()->all(),
                ];
            });

        (new Sites)->setSites($sites)->save();

        $this->newLine();
        $this->info('Sites exported');

        return 0;
    }
}
