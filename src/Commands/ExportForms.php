<?php

namespace Statamic\Eloquent\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Forms\Form as FormContract;
use Statamic\Contracts\Forms\FormRepository as FormRepositoryContract;
use Statamic\Contracts\Forms\Submission as SubmissionContract;
use Statamic\Eloquent\Forms\Form;
use Statamic\Eloquent\Forms\FormRepository;
use Statamic\Eloquent\Forms\Submission;
use Statamic\Forms\Form as StacheForm;
use Statamic\Forms\FormRepository as StacheFormRepository;
use Statamic\Forms\Submission as StacheSubmission;
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
        Facade::clearResolvedInstance(SubmissionContract::class);

        app()->bind(FormContract::class, Form::class);
        app()->bind(SubmissionContract::class, Submission::class);

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

            $newForm->save();

            Statamic::repository(FormRepositoryContract::class, FormRepository::class);

            $form->submissions()->each(function ($submission) use ($newForm) {
                $id = $submission->date()->getPreciseTimestamp(4);
                $id = substr($id, 0, -4).'.'.substr($id, -4);

                $newSubmission = (new StacheSubmission)
                    ->id($id)
                    ->form($newForm)
                    ->data($submission->data());

                $newSubmission->save();
            });
        });

        $this->newLine();
        $this->info('Forms exported');
    }
}
