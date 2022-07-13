<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Forms\Form as FormContract;
use Statamic\Contracts\Forms\FormRepository as FormRepositoryContract;
use Statamic\Eloquent\Forms\Form;
use Statamic\Forms\FormRepository;
use Statamic\Statamic;

class ImportForms extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:import-forms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file based forms and submissions into the database.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->useDefaultRepositories();

        $this->importForms();

        return 0;
    }

    private function useDefaultRepositories()
    {
        Statamic::repository(FormRepositoryContract::class, FormRepository::class);

        // bind to the eloquent container class so we can use toModel()
        app()->bind(FormContract::class, Form::class);
    }

    private function importForms()
    {
        $forms = \Statamic\Facades\Form::all();
        $bar = $this->output->createProgressBar($forms->count());

        $forms->each(function ($form) use ($bar) {
            $model = $form->toModel();
            $model->save();

            $form->fileSubmissions()->each(function ($submission) use ($model) {
                $model->submissions()->create([
                    'created_at' => $submission->date(),
                    'data' => $submission->data(),
                ]);
            });

            $bar->advance();
        });

        $bar->finish();
        $this->line('');
        $this->info('Forms imported');
    }
}
