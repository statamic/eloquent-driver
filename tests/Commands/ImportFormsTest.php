<?php

namespace Tests\Commands;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Contracts\Forms\Form as FormContract;
use Statamic\Contracts\Forms\FormRepository as FormRepositoryContract;
use Statamic\Contracts\Forms\Submission as SubmissionContract;
use Statamic\Contracts\Forms\SubmissionRepository as SubmissionRepositoryContract;
use Statamic\Eloquent\Forms\FormModel;
use Statamic\Eloquent\Forms\SubmissionModel;
use Statamic\Eloquent\Forms\SubmissionQueryBuilder;
use Statamic\Facades\Form;
use Statamic\Forms\FormRepository;
use Statamic\Forms\Submission;
use Statamic\Stache\Repositories\SubmissionRepository;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportFormsTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function setUp(): void
    {
        parent::setUp();

        Facade::clearResolvedInstance(FormRepositoryContract::class);
        Facade::clearResolvedInstance(SubmissionRepositoryContract::class);

        app()->bind(FormContract::class, \Statamic\Forms\Form::class);
        app()->bind(SubmissionContract::class, Submission::class);
        app()->bind(FormRepositoryContract::class, FormRepository::class);
        app()->bind(SubmissionRepositoryContract::class, SubmissionRepository::class);
        app()->bind(SubmissionQueryBuilder::class, \Statamic\Stache\Query\SubmissionQueryBuilder::class);
    }

    #[Test]
    public function it_imports_forms_and_submissions()
    {
        $form = tap(Form::make('contact')->title('Contact')->store(true))->save();
        $form->makeSubmission()->data(['name' => 'Jack'])->save();
        $form->makeSubmission()->data(['name' => 'Jason'])->save();
        $form->makeSubmission()->data(['name' => 'Jesse'])->save();

        $this->assertCount(0, FormModel::all());
        $this->assertCount(0, SubmissionModel::all());

        $this->artisan('statamic:eloquent:import-forms')
            ->expectsQuestion('Do you want to import forms?', true)
            ->expectsQuestion('Do you want to import form submissions?', true)
            ->expectsOutputToContain('Forms imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, FormModel::all());
        $this->assertCount(3, SubmissionModel::all());

        $this->assertDatabaseHas('forms', ['handle' => 'contact', 'title' => 'Contact']);
        $this->assertDatabaseHas('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jack"}']);
        $this->assertDatabaseHas('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jason"}']);
        $this->assertDatabaseHas('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jesse"}']);
    }

    #[Test]
    public function it_imports_form_fields_connections_and_charts()
    {
        $fields = [
            'sections' => [
                [
                    'fields' => [
                        ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
                    ],
                ],
            ],
        ];

        $connections = [
            'email' => [
                ['id' => 'abcdefgh', 'to' => '{{ email }}', 'subject' => 'Thanks'],
            ],
            'webhook' => [
                ['url' => 'https://example.com/webhook'],
            ],
        ];

        $charts = [
            ['field' => 'name', 'chart' => 'popular_answers'],
        ];

        tap(Form::make('contact')->title('Contact')->formFields($fields)->connections($connections)->charts($charts))->save();

        $this->artisan('statamic:eloquent:import-forms', ['--force' => true])
            ->expectsOutputToContain('Forms imported successfully.')
            ->assertExitCode(0);

        $model = FormModel::where('handle', 'contact')->first();

        $this->assertSame($fields, Arr::get($model->settings, 'fields'));
        $this->assertSame($connections, Arr::get($model->settings, 'connections'));
        $this->assertSame($charts, Arr::get($model->settings, 'charts'));
    }

    #[Test]
    public function it_imports_forms_and_submissions_with_force_argument()
    {
        $form = tap(Form::make('contact')->title('Contact')->store(true))->save();
        $form->makeSubmission()->data(['name' => 'Jack'])->save();
        $form->makeSubmission()->data(['name' => 'Jason'])->save();
        $form->makeSubmission()->data(['name' => 'Jesse'])->save();

        $this->assertCount(0, FormModel::all());
        $this->assertCount(0, SubmissionModel::all());

        $this->artisan('statamic:eloquent:import-forms', ['--force' => true])
            ->expectsOutputToContain('Forms imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, FormModel::all());
        $this->assertCount(3, SubmissionModel::all());

        $this->assertDatabaseHas('forms', ['handle' => 'contact', 'title' => 'Contact']);
        $this->assertDatabaseHas('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jack"}']);
        $this->assertDatabaseHas('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jason"}']);
        $this->assertDatabaseHas('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jesse"}']);
    }

    #[Test]
    public function it_imports_only_forms_with_only_forms_argument()
    {
        $form = tap(Form::make('contact')->title('Contact')->store(true))->save();
        $form->makeSubmission()->data(['name' => 'Jack'])->save();
        $form->makeSubmission()->data(['name' => 'Jason'])->save();
        $form->makeSubmission()->data(['name' => 'Jesse'])->save();

        $this->assertCount(0, FormModel::all());
        $this->assertCount(0, SubmissionModel::all());

        $this->artisan('statamic:eloquent:import-forms', ['--only-forms' => true])
            ->expectsOutputToContain('Forms imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, FormModel::all());
        $this->assertCount(0, SubmissionModel::all());

        $this->assertDatabaseHas('forms', ['handle' => 'contact', 'title' => 'Contact']);
        $this->assertDatabaseMissing('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jack"}']);
        $this->assertDatabaseMissing('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jason"}']);
        $this->assertDatabaseMissing('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jesse"}']);
    }

    #[Test]
    public function it_imports_only_forms_with_console_question()
    {
        $form = tap(Form::make('contact')->title('Contact')->store(true))->save();
        $form->makeSubmission()->data(['name' => 'Jack'])->save();
        $form->makeSubmission()->data(['name' => 'Jason'])->save();
        $form->makeSubmission()->data(['name' => 'Jesse'])->save();

        $this->assertCount(0, FormModel::all());
        $this->assertCount(0, SubmissionModel::all());

        $this->artisan('statamic:eloquent:import-forms')
            ->expectsQuestion('Do you want to import forms?', true)
            ->expectsQuestion('Do you want to import form submissions?', false)
            ->expectsOutputToContain('Forms imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, FormModel::all());
        $this->assertCount(0, SubmissionModel::all());

        $this->assertDatabaseHas('forms', ['handle' => 'contact', 'title' => 'Contact']);
        $this->assertDatabaseMissing('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jack"}']);
        $this->assertDatabaseMissing('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jason"}']);
        $this->assertDatabaseMissing('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jesse"}']);
    }

    #[Test]
    public function it_imports_only_submissions_with_only_form_submissions_argument()
    {
        $form = tap(Form::make('contact')->title('Contact')->store(true))->save();
        $form->makeSubmission()->data(['name' => 'Jack'])->save();
        $form->makeSubmission()->data(['name' => 'Jason'])->save();
        $form->makeSubmission()->data(['name' => 'Jesse'])->save();

        $this->assertCount(0, FormModel::all());
        $this->assertCount(0, SubmissionModel::all());

        $this->artisan('statamic:eloquent:import-forms', ['--only-form-submissions' => true])
            ->expectsOutputToContain('Forms imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(0, FormModel::all());
        $this->assertCount(3, SubmissionModel::all());

        $this->assertDatabaseMissing('forms', ['handle' => 'contact', 'title' => 'Contact']);
        $this->assertDatabaseHas('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jack"}']);
        $this->assertDatabaseHas('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jason"}']);
        $this->assertDatabaseHas('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jesse"}']);
    }

    #[Test]
    public function it_imports_only_form_submissions_with_console_question()
    {
        $form = tap(Form::make('contact')->title('Contact')->store(true))->save();
        $form->makeSubmission()->data(['name' => 'Jack'])->save();
        $form->makeSubmission()->data(['name' => 'Jason'])->save();
        $form->makeSubmission()->data(['name' => 'Jesse'])->save();

        $this->assertCount(0, FormModel::all());
        $this->assertCount(0, SubmissionModel::all());

        $this->artisan('statamic:eloquent:import-forms')
            ->expectsQuestion('Do you want to import forms?', false)
            ->expectsQuestion('Do you want to import form submissions?', true)
            ->expectsOutputToContain('Forms imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(0, FormModel::all());
        $this->assertCount(3, SubmissionModel::all());

        $this->assertDatabaseMissing('forms', ['handle' => 'contact', 'title' => 'Contact']);
        $this->assertDatabaseHas('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jack"}']);
        $this->assertDatabaseHas('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jason"}']);
        $this->assertDatabaseHas('form_submissions', ['form' => 'contact', 'data' => '{"name":"Jesse"}']);
    }
}
