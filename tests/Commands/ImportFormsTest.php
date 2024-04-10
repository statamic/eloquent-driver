<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\Facade;
use Statamic\Contracts\Forms\Form as FormContract;
use Statamic\Contracts\Forms\FormRepository as FormRepositoryContract;
use Statamic\Contracts\Forms\Submission as SubmissionContract;
use Statamic\Contracts\Forms\SubmissionRepository as SubmissionRepositoryContract;
use Statamic\Eloquent\Forms\FormModel;
use Statamic\Eloquent\Forms\SubmissionModel;
use Tests\PreventSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportFormsTest extends TestCase
{
    use PreventSavingStacheItemsToDisk;

    public function setUp(): void
    {
        parent::setUp();

        Facade::clearResolvedInstance(FormRepositoryContract::class);
        Facade::clearResolvedInstance(SubmissionRepositoryContract::class);

        app()->bind(FormContract::class, \Statamic\Forms\Form::class);
        app()->bind(SubmissionContract::class, \Statamic\Forms\Submission::class);
        app()->bind(FormRepositoryContract::class, \Statamic\Forms\FormRepository::class);
        app()->bind(SubmissionRepositoryContract::class, \Statamic\Stache\Repositories\SubmissionRepository::class);
        app()->bind(\Statamic\Eloquent\Forms\SubmissionQueryBuilder::class, \Statamic\Stache\Query\SubmissionQueryBuilder::class);
        app()->bind('statamic.eloquent.forms.submission_model', \Statamic\Eloquent\Forms\SubmissionModel::class);
    }

    /** @test */
    public function it_imports_forms_and_submissions()
    {
        $form = tap(\Statamic\Facades\Form::make('contact')->title('Contact')->store(true))->save();
        $submissionA = tap($form->makeSubmission())->data(['name' => 'Jack'])->save();
        $submissionB = tap($form->makeSubmission())->data(['name' => 'Jason'])->save();
        $submissionC = tap($form->makeSubmission())->data(['name' => 'Jesse'])->save();

        $this->assertCount(0, FormModel::all());
        $this->assertCount(0, SubmissionModel::all());

        $this->artisan('statamic:eloquent:import-forms')
            ->expectsOutput('Forms imported')
            ->assertExitCode(0);

        $this->assertCount(1, FormModel::all());
        $this->assertCount(3, SubmissionModel::all());
    }

    /** @test */
    public function it_imports_only_forms()
    {
        $form = tap(\Statamic\Facades\Form::make('contact')->title('Contact')->store(true))->save();
        $submissionA = tap($form->makeSubmission())->data(['name' => 'Jack'])->save();
        $submissionB = tap($form->makeSubmission())->data(['name' => 'Jason'])->save();
        $submissionC = tap($form->makeSubmission())->data(['name' => 'Jesse'])->save();

        $this->assertCount(0, FormModel::all());
        $this->assertCount(0, SubmissionModel::all());

        $this->artisan('statamic:eloquent:import-forms', ['--only-forms' => true])
            ->expectsOutput('Forms imported')
            ->assertExitCode(0);

        $this->assertCount(1, FormModel::all());
        $this->assertCount(0, SubmissionModel::all());
    }

    /** @test */
    public function it_imports_only_submissions()
    {
        $form = tap(\Statamic\Facades\Form::make('contact')->title('Contact')->store(true))->save();
        $submissionA = tap($form->makeSubmission())->data(['name' => 'Jack'])->save();
        $submissionB = tap($form->makeSubmission())->data(['name' => 'Jason'])->save();
        $submissionC = tap($form->makeSubmission())->data(['name' => 'Jesse'])->save();

        $this->assertCount(0, FormModel::all());
        $this->assertCount(0, SubmissionModel::all());

        $this->artisan('statamic:eloquent:import-forms', ['--only-form-submissions' => true])
            ->expectsOutput('Forms imported')
            ->assertExitCode(0);

        $this->assertCount(0, FormModel::all());
        $this->assertCount(3, SubmissionModel::all());
    }
}
