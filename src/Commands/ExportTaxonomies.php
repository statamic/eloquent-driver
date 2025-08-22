<?php

namespace Statamic\Eloquent\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Taxonomies\Taxonomy as TaxonomyContract;
use Statamic\Contracts\Taxonomies\TaxonomyRepository as TaxonomyRepositoryContract;
use Statamic\Contracts\Taxonomies\Term as TermContract;
use Statamic\Contracts\Taxonomies\TermRepository as TermRepositoryContract;
use Statamic\Eloquent\Taxonomies\TaxonomyModel as EloquentTaxonomy;
use Statamic\Eloquent\Taxonomies\TermModel as EloquentTerm;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Statamic\Facades\Term as TermFacade;
use Statamic\Stache\Repositories\TaxonomyRepository;
use Statamic\Stache\Repositories\TermRepository;
use Statamic\Statamic;
use Statamic\Taxonomies\Taxonomy as StacheTaxonomy;
use Statamic\Taxonomies\Term as StacheTerm;

class ExportTaxonomies extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:export-taxonomies
        {--force : Force the export to run, with all prompts answered "yes"}
        {--only-taxonomies : Only export taxonomies}
        {--only-terms : Only export terms}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports eloquent taxonomies and terms to flat files.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->usingDefaultRepositories(function () {
            $this->exportTaxonomies();
            $this->exportTerms();
        });

        return 0;
    }

    private function usingDefaultRepositories(Closure $callback)
    {
        Facade::clearResolvedInstance(TaxonomyRepositoryContract::class);
        Facade::clearResolvedInstance(TermRepositoryContract::class);

        Statamic::repository(TaxonomyRepositoryContract::class, TaxonomyRepository::class);
        Statamic::repository(TermRepositoryContract::class, TermRepository::class);

        app()->bind(TaxonomyContract::class, StacheTaxonomy::class);
        app()->bind(TermContract::class, StacheTerm::class);

        $callback();
    }

    private function exportTaxonomies()
    {
        if (! $this->shouldExportTaxonomies()) {
            return;
        }

        $taxonomies = EloquentTaxonomy::all();

        $this->withProgressBar($taxonomies, function ($model) {
            TaxonomyFacade::make()
                ->handle($model->handle)
                ->title($model->title)
                ->sites($model->sites)
                ->revisionsEnabled($model->settings['revisions'] ?? false)
                ->save();
        });

        $this->newLine();
        $this->info('Taxonomies exported');
    }

    private function exportTerms()
    {
        if (! $this->shouldExportTerms()) {
            return;
        }

        $terms = EloquentTerm::all();

        $this->withProgressBar($terms, function ($model) {
            $data = $model->data;

            $term = TermFacade::make()
                ->slug($model->slug)
                ->taxonomy($model->taxonomy)
                ->blueprint($model->data['blueprint'] ?? null);

            collect($data['localizations'] ?? [])
                ->except($term->defaultLocale())
                ->each(function ($localeData, $locale) use ($term) {
                    $term->dataForLocale($locale, $localeData);
                });

            unset($data['localizations']);

            if (isset($data['collection'])) {
                $term->collection($data['collection']);
                unset($data['collection']);
            }

            $term->syncOriginal();
            $term->data($data);

            if (config('statamic.system.track_last_update')) {
                $term->set('updated_at', $model->updated_at ?? $model->created_at);
            }

            $term->save();
        });

        $this->newLine();
        $this->info('Terms exported');
    }

    private function shouldExportTaxonomies(): bool
    {
        return $this->option('only-taxonomies')
            || ! $this->option('only-terms')
            && ($this->option('force') || $this->confirm('Do you want to export taxonomies?'));
    }

    private function shouldExportTerms(): bool
    {
        return $this->option('only-terms')
            || ! $this->option('only-taxonomies')
            && ($this->option('force') || $this->confirm('Do you want to export terms?'));
    }
}
