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
use Statamic\Eloquent\Forms\FormModel;
use Statamic\Eloquent\Forms\SubmissionModel;
use Statamic\Facades\Form;
use Statamic\Facades\FormSubmission;
use Statamic\Forms\Form as StacheForm;
use Statamic\Forms\FormRepository as StacheFormRepository;
use Statamic\Forms\Submission as StacheSubmission;
use Statamic\Stache\Repositories\SubmissionRepository as StacheSubmissionRepository;

class ExportForms extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:export-forms
        {--force : Force the export to run, with all prompts answered "yes"}
        {--only-forms : Only export forms}
        {--only-submissions : Only export submissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export eloquent based forms and submissions to flat files.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->usingDefaultRepositories(function () {
            $this->exportForms();
            $this->exportSubmissions();
        });

        return 0;
    }

    private function usingDefaultRepositories(Closure $callback)
    {
        Facade::clearResolvedInstance(FormContract::class);
        Facade::clearResolvedInstance(FormRepositoryContract::class);
        Facade::clearResolvedInstance(SubmissionContract::class);
        Facade::clearResolvedInstance(SubmissionRepositoryContract::class);

        app()->bind(FormContract::class, StacheForm::class);
        app()->bind(FormRepositoryContract::class, StacheFormRepository::class);
        app()->bind(SubmissionContract::class, StacheSubmission::class);
        app()->bind(SubmissionRepositoryContract::class, StacheSubmissionRepository::class);

        $callback();
    }

    private function exportForms()
    {
        if (! $this->shouldExportForms()) {
            return;
        }

        $forms = FormModel::all();

        $this->withProgressBar($forms, function ($form) {
            Form::make()
                ->handle($form->handle)
                ->title($form->title)
                ->store($form->settings['store'] ?? null)
                ->email($form->settings['email'] ?? null)
                ->honeypot($form->settings['honeypot'] ?? null)
                ->data($form->settings['data'] ?? [])
                ->save();
        });

        $this->newLine();
        $this->info('Forms exported');
    }

    private function exportSubmissions()
    {
        if (! $this->shouldExportSubmissions()) {
            return;
        }

        $submissions = SubmissionModel::all();

        $this->withProgressBar($submissions, function ($submission) {
            if (! $form = Form::find($submission->form)) {
                return;
            }

            $id = $submission->created_at->getPreciseTimestamp(4);
            $id = substr($id, 0, -4).'.'.substr($id, -4);

            FormSubmission::make()
                ->id($id)
                ->form($form)
                ->data($submission->data)
                ->save();
        });

        $this->newLine();
        $this->info('Submissions exported');
    }

    private function shouldExportForms(): bool
    {
        return $this->option('only-forms')
            || ! $this->option('only-submissions')
            && ($this->option('force') || $this->confirm('Do you want to export forms?'));
    }

    private function shouldExportSubmissions(): bool
    {
        return $this->option('only-submissions')
            || ! $this->option('only-forms')
            && ($this->option('force') || $this->confirm('Do you want to export submissions?'));
    }
}
