<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Forms\Form as FormContract;
use Statamic\Contracts\Forms\Submission as SubmissionContract;
use Statamic\Contracts\Forms\FormRepository as FormRepositoryContract;
use Statamic\Eloquent\Forms\Form;
use Statamic\Forms\Form as StacheForm;
use Statamic\Forms\FormRepository;
use Statamic\Forms\Submission as StacheSubmission;
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
        app()->bind(FormContract::class, StacheForm::class);
        app()->bind(SubmissionContract::class, StacheSubmission::class);
    }

    private function importForms()
    {
        $forms = (new \Statamic\Forms\FormRepository)->all();
        $bar = $this->output->createProgressBar($forms->count());

        $forms->each(function ($form) use ($bar) {
            $model = Form::makeModelFromContract($form);
            $model->save();

            $form->submissions()->each(function ($submission) use ($model) {
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
