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
        {--force : Force the import to run, with all prompts answered "yes"}
        {--only-forms : Only import forms}
        {--only-form-submissions : Only import submissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file-based forms & form submissions into the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->useDefaultRepositories();

        $this->importForms();

        return 0;
    }

    private function useDefaultRepositories(): void
    {
        Facade::clearResolvedInstance(FormContract::class);
        Facade::clearResolvedInstance(SubmissionContract::class);

        app()->bind(FormContract::class, StacheForm::class);
        app()->bind(SubmissionContract::class, StacheSubmission::class);
        app()->bind(\Statamic\Eloquent\Forms\SubmissionQueryBuilder::class, \Statamic\Stache\Query\SubmissionQueryBuilder::class);
    }

    private function importForms(): void
    {
        $this->withProgressBar((new FormRepository)->all(), function ($form) {
            if ($this->shouldImportForms()) {
                $lastModified = Carbon::createFromTimestamp(File::lastModified($form->path()));

                Form::makeModelFromContract($form)
                    ->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])
                    ->save();
            }

            if ($this->shouldImportFormSubmissions()) {
                $form->submissions()->each(function ($submission) use ($form) {
                    $timestamp = app('statamic.eloquent.form_submissions.model')::make()->fromDateTime($submission->date());

                    app('statamic.eloquent.form_submissions.model')::firstOrNew(['created_at' => $timestamp])
                        ->fill([
                            'form' => $form->handle(),
                            'data' => $submission->data(),
                            'updated_at' => $timestamp,
                        ])
                        ->save();
                });
            }
        });

        $this->components->info('Forms imported successfully.');
    }

    private function shouldImportForms(): bool
    {
        return $this->option('only-forms')
            || ! $this->option('only-form-submissions')
            && ($this->option('force') || $this->confirm('Do you want to import forms?'));
    }

    private function shouldImportFormSubmissions(): bool
    {
        return $this->option('only-form-submissions')
            || ! $this->option('only-forms')
            && ($this->option('force') || $this->confirm('Do you want to import form submissions?'));
    }
}
