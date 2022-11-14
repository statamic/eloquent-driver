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

        app()->bind(TaxonomyContract::class, StacheTaxonomy::class);
        app()->bind(TermContract::class, StacheTerm::class);
    }

    private function importTaxonomies()
    {
        if (! $this->confirm('Do you want to import taxonomies?')) {
            return;
        }

        $taxonomies = TaxonomyFacade::all();

        $this->withProgressBar($taxonomies, function ($taxonomy) {
            $lastModified = $taxonomy->fileLastModified();
            EloquentTaxonomy::makeModelFromContract($taxonomy)->fill([
                'created_at' => $lastModified,
                'updated_at' => $lastModified,
            ])->save();
        });

        $this->newLine();
        $this->info('Taxonomies imported');
    }

    private function importTerms()
    {
        if (! $this->confirm('Do you want to import terms?')) {
            return;
        }

        $terms = TermFacade::all();
        // Grab unique parent terms.
        $terms = $terms->map->term()->unique();

        $this->withProgressBar($terms, function ($term) {
            $lastModified = $term->fileLastModified();
            EloquentTerm::makeModelFromContract($term)->fill([
                'created_at' => $lastModified,
                'updated_at' => $lastModified,
            ])->save();
        });

        $this->newLine();
        $this->info('Terms imported');
    }
}
