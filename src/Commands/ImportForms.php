<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Forms\Form as FormContract;
use Statamic\Contracts\Forms\Submission as SubmissionContract;
use Statamic\Eloquent\Forms\Form;
use Statamic\Facades\File;
use Statamic\Forms\Form as StacheForm;
use Statamic\Forms\FormRepository;
use Statamic\Forms\Submission as StacheSubmission;

class ImportForms extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:import-forms
        {--only-forms : Only import forms}
        {--only-form-submissions : Only import submissions}';

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
        Facade::clearResolvedInstance(FormContract::class);
        Facade::clearResolvedInstance(SubmissionContract::class);

        app()->bind(FormContract::class, StacheForm::class);
        app()->bind(SubmissionContract::class, StacheSubmission::class);
    }

    private function importForms()
    {
        $importForms = $this->option('only-form-submissions') ? false : true;
        $importSubmissions = $this->option('only-forms') ? false : true;

        $forms = (new FormRepository())->all();

        $this->withProgressBar($forms, function ($form) use ($importForms, $importSubmissions) {
            if ($importForms) {
                $lastModified = Carbon::createFromTimestamp(File::lastModified($form->path()));

                Form::makeModelFromContract($form)
                    ->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])
                    ->save();
            }

            if ($importSubmissions) {
                $form->submissions()->each(function ($submission) use ($form) {
                    $timestamp = app('statamic.eloquent.forms.submission_model')::make()->fromDateTime($submission->date());

                    app('statamic.eloquent.forms.submission_model')
                        ->where('form', $form->handle())
                        ->firstOrNew(['created_at' => $timestamp])
                        ->fill([
                            'data' => $submission->data(),
                            'updated_at' => $timestamp,
                        ])
                        ->save();
                });
            }
        });

        $this->newLine();
        $this->info('Forms imported');
    }
}
