<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
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
        app()->bind(FormContract::class, StacheForm::class);
        app()->bind(SubmissionContract::class, StacheSubmission::class);
    }

    private function importForms()
    {
        $forms = (new FormRepository())->all();

        $this->withProgressBar($forms, function ($form) {
            $lastModified = Carbon::createFromTimestamp(File::lastModified($form->path()));
            $model = Form::makeModelFromContract($form)->fill([
                'created_at' => $lastModified,
                'updated_at' => $lastModified,
            ]);
            $model->save();

            $form->submissions()->each(function ($submission) use ($model) {
                $timestamp = app('statamic.eloquent.forms.submission_model')::make()->fromDateTime($submission->date());

                $model->submissions()->firstOrNew(['created_at' => $timestamp])->fill([
                    'data'       => $submission->data(),
                    'updated_at' => $timestamp,
                ])->save();
            });
        });

        $this->newLine();
        $this->info('Forms imported');
    }
}
