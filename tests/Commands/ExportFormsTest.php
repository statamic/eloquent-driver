<?php

namespace Tests\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Forms\FormModel;
use Statamic\Eloquent\Forms\SubmissionModel;
use Statamic\Facades\File;
use Statamic\Facades\YAML;
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
    public function it_exports_form_fields_connections_and_charts()
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

        FormModel::create([
            'handle' => 'contact',
            'title' => 'Contact',
            'settings' => [
                'fields' => $fields,
                'charts' => $charts,
                'honeypot' => 'winnie',
                'connections' => $connections,
                'store' => true,
            ],
        ]);

        $this->artisan('statamic:eloquent:export-forms', ['--only-forms' => true])
            ->expectsOutputToContain('Forms exported')
            ->assertExitCode(0);

        $contents = YAML::parse(File::get($this->formsDir.'/contact.yaml'));

        $this->assertSame($fields, $contents['fields']);
        $this->assertSame($connections, $contents['connections']);
        $this->assertSame($charts, $contents['charts']);
        $this->assertSame('winnie', $contents['honeypot']);
    }

    #[Test]
    public function it_converts_legacy_email_settings_when_exporting()
    {
        FormModel::create([
            'handle' => 'contact',
            'title' => 'Contact',
            'settings' => [
                'store' => true,
                'email' => [
                    ['to' => 'foo@bar.com', 'subject' => 'Feedback'],
                ],
            ],
        ]);

        $this->artisan('statamic:eloquent:export-forms', ['--only-forms' => true])
            ->expectsOutputToContain('Forms exported')
            ->assertExitCode(0);

        $contents = YAML::parse(File::get($this->formsDir.'/contact.yaml'));

        $this->assertArrayNotHasKey('email', $contents);
        $this->assertSame('foo@bar.com', $contents['connections']['email'][0]['to']);
        $this->assertSame('Feedback', $contents['connections']['email'][0]['subject']);
        $this->assertNotNull($contents['connections']['email'][0]['id']);
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
