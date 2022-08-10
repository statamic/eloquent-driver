<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Taxonomies\Taxonomy as TaxonomyContract;
use Statamic\Contracts\Taxonomies\TaxonomyRepository as TaxonomyRepositoryContract;
use Statamic\Contracts\Taxonomies\Term as TermContract;
use Statamic\Contracts\Taxonomies\TermRepository as TermRepositoryContract;
use Statamic\Eloquent\Taxonomies\Taxonomy as EloquentTaxonomy;
use Statamic\Eloquent\Taxonomies\Term as EloquentTerm;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Statamic\Facades\Term as TermFacade;
use Statamic\Stache\Repositories\TaxonomyRepository;
use Statamic\Stache\Repositories\TermRepository;
use Statamic\Statamic;
use Statamic\Taxonomies\Taxonomy as StacheTaxonomy;
use Statamic\Taxonomies\Term as StacheTerm;

class ImportTaxonomies extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:import-taxonomies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file based taxonomies and terms into the database.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->useDefaultRepositories();

        $this->importTaxonomies();
        $this->importTerms();

        return 0;
    }

    private function useDefaultRepositories()
    {
        Statamic::repository(TaxonomyRepositoryContract::class, TaxonomyRepository::class);
        Statamic::repository(TermRepositoryContract::class, TermRepository::class);

        // bind to the eloquent container class so we can use toModel()
        app()->bind(TaxonomyContract::class, StacheTaxonomy::class);
        app()->bind(TermContract::class, StacheTerm::class);
    }

    private function importTaxonomies()
    {
        $taxonomies = TaxonomyFacade::all();
        $bar = $this->output->createProgressBar($taxonomies->count());

        $taxonomies->each(function ($taxonomy) use ($bar) {
            $model = tap(EloquentTaxonomy::makeModelFromContract($taxonomy))->save();

            $bar->advance();
        });

        $bar->finish();
        $this->line('');
        $this->info('Taxonomies imported');
    }

    private function importTerms()
    {
        $terms = TermFacade::all();

        $parentTerms = collect();
        $terms->each(function ($term) use ($parentTerms) {
            $parentTerms->push($term->term());
        });

        $parentTerms = $parentTerms->unique();

        $bar = $this->output->createProgressBar($parentTerms->count());

        $parentTerms->each(function ($term) use ($bar) {
            $model = tap(EloquentTerm::makeModelFromContract($term))->save();

            $bar->advance();
        });

        $bar->finish();
        $this->line('');
        $this->info('Terms imported');
    }
}
