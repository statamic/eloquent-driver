<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
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
    protected $signature = 'statamic:eloquent:import-taxonomies
        {--force : Force the import to run, with all prompts answered "yes"}
        {--only-taxonomies : Only import taxonomies}
        {--only-terms : Only import terms}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file-based taxonomies & terms into the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->useDefaultRepositories();

        $this->importTaxonomies();
        $this->importTerms();

        return 0;
    }

    private function useDefaultRepositories(): void
    {
        Facade::clearResolvedInstance(TaxonomyRepositoryContract::class);
        Facade::clearResolvedInstance(TermRepositoryContract::class);

        Statamic::repository(TaxonomyRepositoryContract::class, TaxonomyRepository::class);
        Statamic::repository(TermRepositoryContract::class, TermRepository::class);

        app()->bind(TaxonomyContract::class, StacheTaxonomy::class);
        app()->bind(TermContract::class, StacheTerm::class);
    }

    private function importTaxonomies(): void
    {
        if (! $this->shouldImportTaxonomies()) {
            return;
        }

        $this->withProgressBar(TaxonomyFacade::all(), function ($taxonomy) {
            $lastModified = $taxonomy->fileLastModified();

            EloquentTaxonomy::makeModelFromContract($taxonomy)
                ->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])
                ->save();
        });

        $this->components->info('Taxonomies imported successfully.');
    }

    private function importTerms(): void
    {
        if (! $this->shouldImportTerms()) {
            return;
        }

        $this->withProgressBar(TermFacade::all()->map->term()->unique(), function ($term) {
            $lastModified = $term->fileLastModified();

            EloquentTerm::makeModelFromContract($term)
                ->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])
                ->save();
        });

        $this->components->info('Terms imported successfully.');
    }

    private function shouldImportTaxonomies(): bool
    {
        return $this->option('only-taxonomies')
            || ! $this->option('only-terms')
            && ($this->option('force') || $this->confirm('Do you want to import taxonomies?'));
    }

    private function shouldImportTerms(): bool
    {
        return $this->option('only-terms')
            || ! $this->option('only-taxonomies')
            && ($this->option('force') || $this->confirm('Do you want to import terms?'));
    }
}
