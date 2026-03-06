<?php

namespace Tests\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Forms\FormModel;
use Statamic\Eloquent\Forms\SubmissionModel;
use Tests\TestCase;

class ExportFormsTest extends TestCase
{
    use RefreshDatabase;

    private string $formsDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formsDir = __DIR__.'/../__fixtures__/dev-null/content/forms';
        config()->set('statamic.forms.forms', $this->formsDir);
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory($this->formsDir);

        parent::tearDown();
    }

    #[Test]
    public function it_exports_forms_and_submissions()
    {
        $form = FormModel::create(['handle' => 'contact', 'title' => 'Contact', 'settings' => ['store' => true]]);
        SubmissionModel::create(['id' => '1234567890.0001', 'form' => 'contact', 'data' => ['name' => 'Jack']]);
        SubmissionModel::create(['id' => '1234567890.0002', 'form' => 'contact', 'data' => ['name' => 'Jane']]);

        $this->artisan('statamic:eloquent:export-forms', ['--force' => true])
            ->expectsOutputToContain('Forms exported')
            ->expectsOutputToContain('Submissions exported')
            ->assertExitCode(0);

        $this->assertFileExists($this->formsDir.'/contact.yaml');
    }

    #[Test]
    public function it_exports_forms_with_console_question()
    {
        FormModel::create(['handle' => 'contact', 'title' => 'Contact', 'settings' => ['store' => true]]);
        SubmissionModel::create(['id' => '1234567890.0001', 'form' => 'contact', 'data' => ['name' => 'Jack']]);

        $this->artisan('statamic:eloquent:export-forms')
            ->expectsQuestion('Do you want to export forms?', true)
            ->expectsQuestion('Do you want to export submissions?', false)
            ->expectsOutputToContain('Forms exported')
            ->doesntExpectOutputToContain('Submissions exported')
            ->assertExitCode(0);

        $this->assertFileExists($this->formsDir.'/contact.yaml');
    }

    #[Test]
    public function it_exports_only_forms_with_only_forms_argument()
    {
        FormModel::create(['handle' => 'contact', 'title' => 'Contact', 'settings' => ['store' => true]]);
        SubmissionModel::create(['id' => '1234567890.0001', 'form' => 'contact', 'data' => ['name' => 'Jack']]);

        $this->artisan('statamic:eloquent:export-forms', ['--only-forms' => true])
            ->expectsOutputToContain('Forms exported')
            ->doesntExpectOutputToContain('Submissions exported')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_exports_only_submissions_with_only_submissions_argument()
    {
        FormModel::create(['handle' => 'contact', 'title' => 'Contact', 'settings' => ['store' => true]]);
        SubmissionModel::create(['id' => '1234567890.0001', 'form' => 'contact', 'data' => ['name' => 'Jack']]);

        $this->artisan('statamic:eloquent:export-forms', ['--only-submissions' => true])
            ->doesntExpectOutputToContain('Forms exported')
            ->expectsOutputToContain('Submissions exported')
            ->assertExitCode(0);
    }
}
