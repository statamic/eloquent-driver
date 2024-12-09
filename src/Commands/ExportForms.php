<?php

namespace Statamic\Eloquent\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Forms\Form as FormContract;
use Statamic\Contracts\Forms\FormRepository as FormRepositoryContract;
use Statamic\Contracts\Forms\Submission as SubmissionContract;
use Statamic\Contracts\Forms\SubmissionRepository as SubmissionRepositoryContract;
use Statamic\Eloquent\Forms\Form;
use Statamic\Eloquent\Forms\FormRepository;
use Statamic\Eloquent\Forms\Submission;
use Statamic\Eloquent\Forms\SubmissionRepository;
use Statamic\Forms\Form as StacheForm;
use Statamic\Forms\FormRepository as StacheFormRepository;
use Statamic\Forms\Submission as StacheSubmission;
use Statamic\Stache\Repositories\SubmissionRepository as StacheSubmissionRepository;
use Statamic\Statamic;

class ExportForms extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:export-forms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export eloquent based forms to flat files.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->usingDefaultRepositories(function () {
            $this->exportForms();
        });

        $this->newLine();
        $this->info('Forms exported');

        return 0;
    }

    private function usingDefaultRepositories(Closure $callback)
    {
        Facade::clearResolvedInstance(FormContract::class);
        Facade::clearResolvedInstance(FormRepositoryContract::class);
        Facade::clearResolvedInstance(SubmissionContract::class);
        Facade::clearResolvedInstance(SubmissionRepositoryContract::class);

        app()->bind(FormContract::class, Form::class);
        app()->bind(FormRepositoryContract::class, FormRepository::class);
        app()->bind(SubmissionContract::class, Submission::class);
        app()->bind(SubmissionRepositoryContract::class, SubmissionRepository::class);
        app()->bind(\Statamic\Contracts\Forms\SubmissionQueryBuilder::class, \Statamic\Eloquent\Forms\SubmissionQueryBuilder::class);

        $callback();
    }

    private function exportForms()
    {
        $forms = (new FormRepository)->all();

        app()->bind(FormContract::class, StacheForm::class);

        $this->withProgressBar($forms, function ($form) {
            $newForm = (new StacheForm)
                ->handle($form->handle())
                ->title($form->title())
                ->store($form->store())
                ->email($form->email())
                ->honeypot($form->honeypot());

            Statamic::repository(FormRepositoryContract::class, StacheFormRepository::class);
            Facade::clearResolvedInstance(SubmissionRepositoryContract::class);

            $newForm->save();

            Statamic::repository(FormRepositoryContract::class, FormRepository::class);
            Facade::clearResolvedInstance(SubmissionRepositoryContract::class);

            $form->submissions()->each(function ($submission) use ($newForm) {
                $id = $submission->date()->getPreciseTimestamp(4);
                $id = substr($id, 0, -4).'.'.substr($id, -4);

                $newSubmission = (new StacheSubmission)
                    ->id($id)
                    ->form($newForm)
                    ->data($submission->data());

                Statamic::repository(SubmissionRepositoryContract::class, StacheSubmissionRepository::class);
                Facade::clearResolvedInstance(SubmissionRepositoryContract::class);

                $newSubmission->save();

                Statamic::repository(SubmissionRepositoryContract::class, SubmissionRepository::class);
                Facade::clearResolvedInstance(SubmissionRepositoryContract::class);
            });
        });

        $this->newLine();
        $this->info('Forms exported');
    }
}
