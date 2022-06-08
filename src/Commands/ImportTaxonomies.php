<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Taxonomies\Taxonomy as TaxonomyContract;
use Statamic\Contracts\Taxonomies\TaxonomyRepository as TaxonomyRepositoryContract;
use Statamic\Contracts\Taxonomies\Term as TermContract;
use Statamic\Contracts\Taxonomies\TermRepository as TermRepositoryContract;
use Statamic\Eloquent\Taxonomies\Taxonomy;
use Statamic\Eloquent\Taxonomies\Term;
use Statamic\Stache\Repositories\TaxonomyRepository;
use Statamic\Stache\Repositories\TermRepository;
use Statamic\Statamic;

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
        app()->bind(TaxonomyContract::class, Taxonomy::class);
        app()->bind(TermContract::class, Term::class);
    }

    private function importTaxonomies()
    {
        $taxonomies = \Statamic\Facades\Taxonomy::all();
        $bar = $this->output->createProgressBar($taxonomies->count());

        $taxonomies->each(function ($taxonomy) use ($bar) {
            $model = $taxonomy->toModel();
            $model->save();

            $bar->advance();
        });

        $bar->finish();
        $this->line('');
        $this->info('Taxonomies imported');
    }

    private function importTerms()
    {
        $terms = \Statamic\Facades\Term::all();
        $bar = $this->output->createProgressBar($terms->count());

        $terms->each(function ($term) use ($bar) {
            $model = $term->toModel();
            $model->save();

            $bar->advance();
        });

        $bar->finish();
        $this->line('');
        $this->info('Terms imported');
    }
}
