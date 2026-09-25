<?php

namespace Tests\Forms;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Forms\FormModel;
use Statamic\Events\FormCreated;
use Statamic\Events\FormCreating;
use Statamic\Events\FormDeleted;
use Statamic\Events\FormDeleting;
use Statamic\Events\FormSaved;
use Statamic\Events\FormSaving;
use Statamic\Facades;
use Statamic\Facades\Form;
use Statamic\Fields\Blueprint;
use Statamic\Support\Arr;
use Tests\TestCase;

class FormTest extends TestCase
{
    #[Test]
    public function it_saves_a_form()
    {
        Event::fake();

        $blueprint = (new Blueprint)->setHandle('post')->save();

        $form = Form::make('contact_us')
            ->title('Contact Us')
            ->honeypot('winnie')
            ->data([
                'foo' => 'bar',
                'roo' => 'rar',
            ]);

        $form->save();

        $this->assertEquals('contact_us', $form->handle());
        $this->assertEquals('Contact Us', $form->title());
        $this->assertEquals('winnie', $form->honeypot());
        $this->assertEquals([
            'foo' => 'bar',
            'roo' => 'rar',
        ], $form->data()->all());

        Event::assertDispatched(FormCreating::class, function ($event) use ($form) {
            return $event->form === $form;
        });

        Event::assertDispatched(FormSaving::class, function ($event) use ($form) {
            return $event->form === $form;
        });

        Event::assertDispatched(FormCreated::class, function ($event) use ($form) {
            return $event->form === $form;
        });

        Event::assertDispatched(FormSaved::class, function ($event) use ($form) {
            return $event->form === $form;
        });
    }

    #[Test]
    public function finding_a_form_sets_the_blink_cache()
    {
        Form::make('test')->title('Test form')->save();

        $form = Form::find('test');

        $this->assertSame(Facades\Blink::get('eloquent-forms-test'), $form);
    }

    #[Test]
    public function getting_all_forms_sets_the_blink_cache()
    {
        $form = tap(Form::make('test')->title('Test form'))->save();

        Form::all();

        $this->assertCount(1, Facades\Blink::get('eloquent-forms'));
        $this->assertSame($form->handle(), Facades\Blink::get('eloquent-forms')->first()->handle());
    }

    #[Test]
    public function saving_a_form_removes_the_blink_cache()
    {
        Form::make('test')->title('Test form')->save();

        $form = Form::find('test');
        Form::all(); // to set up eloquent-forms blink

        $this->assertSame(Facades\Blink::get('eloquent-forms-test'), $form);
        $this->assertCount(1, Facades\Blink::get('eloquent-forms'));

        $form->save();

        $this->assertNull(Facades\Blink::get('eloquent-forms-test'));
        $this->assertNull(Facades\Blink::get('eloquent-forms'));
    }

    #[Test]
    public function deleting_a_form_removes_the_blink_cache()
    {
        Form::make('test')->title('Test form')->save();

        $form = Form::find('test');
        Form::all(); // to set up eloquent-forms blink

        $this->assertSame(Facades\Blink::get('eloquent-forms-test'), $form);
        $this->assertCount(1, Facades\Blink::get('eloquent-forms'));

        $form->delete();

        $this->assertNull(Facades\Blink::get('eloquent-forms-test'));
        $this->assertNull(Facades\Blink::get('eloquent-forms'));
    }

    #[Test]
    public function it_stores_form_data()
    {
        $form = tap(Form::make('test')->title('Test form')->data(['some' => 'data']))->save();

        $this->assertSame(['some' => 'data'], Arr::get($form->model(), 'settings.data'));
    }

    #[Test]
    public function null_values_are_removed_from_data()
    {
        $form = tap(Form::make('test')->title('Test form')->data(['some' => 'data', 'null_value' => null]))->save();

        $this->assertSame(['some' => 'data'], Arr::get($form->model(), 'settings.data'));
    }

    #[Test]
    public function it_stores_form_fields()
    {
        $fields = [
            'sections' => [
                [
                    'fields' => [
                        ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
                        ['handle' => 'email', 'field' => ['type' => 'email', 'display' => 'Email', 'validate' => ['required']]],
                    ],
                ],
            ],
        ];

        $form = tap(Form::make('test')->title('Test form')->formFields($fields))->save();

        $this->assertSame($fields, Arr::get($form->model(), 'settings.fields'));
        $this->assertSame($fields, Form::find('test')->formFields()->contents());
    }

    #[Test]
    public function it_stores_connections()
    {
        $connections = [
            'email' => [
                ['id' => 'abcdefgh', 'to' => '{{ email }}', 'subject' => 'Thanks for getting in touch'],
            ],
            'webhook' => [
                ['url' => 'https://example.com/webhook'],
            ],
        ];

        $form = tap(Form::make('test')->title('Test form')->connections($connections))->save();

        $this->assertSame($connections, Arr::get($form->model(), 'settings.connections'));
        $this->assertSame($connections, Form::find('test')->connections()->all());
    }

    #[Test]
    public function it_stores_charts()
    {
        $charts = [
            ['field' => 'name', 'chart' => 'popular_answers'],
        ];

        $form = tap(Form::make('test')->title('Test form')->charts($charts))->save();

        $this->assertSame($charts, Arr::get($form->model(), 'settings.charts'));
        $this->assertSame($charts, Form::find('test')->charts());
    }

    #[Test]
    #[DataProvider('legacyEmailProvider')]
    public function legacy_email_settings_are_converted_to_an_email_connection($email)
    {
        FormModel::create([
            'handle' => 'test',
            'title' => 'Test form',
            'settings' => [
                'store' => true,
                'honeypot' => 'winnie',
                'email' => $email,
            ],
        ]);

        $form = Form::find('test');

        $emailConnection = $form->connections()->get('email');

        $this->assertCount(1, $emailConnection);
        $this->assertSame('foo@bar.com', $emailConnection[0]['to']);
        $this->assertSame('Feedback', $emailConnection[0]['subject']);
        $this->assertNotNull($emailConnection[0]['id']);
        $this->assertSame('winnie', $form->honeypot());
    }

    public static function legacyEmailProvider(): array
    {
        return [
            'single email config' => [['to' => 'foo@bar.com', 'subject' => 'Feedback']],
            'multiple email configs' => [[['to' => 'foo@bar.com', 'subject' => 'Feedback']]],
        ];
    }

    #[Test]
    public function fields_fall_back_to_a_legacy_blueprint_file()
    {
        Facades\Blueprint::make('test')
            ->setNamespace('forms')
            ->setContents([
                'tabs' => [
                    'main' => [
                        'sections' => [
                            [
                                'fields' => [
                                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->save();

        FormModel::create(['handle' => 'test', 'title' => 'Test form']);

        $form = Form::find('test');
        $fields = $form->formFields()->contents();

        $this->assertSame('short_answer', Arr::get($fields, 'sections.0.fields.0.field.type'));

        $form->save();

        $this->assertSame($fields, Arr::get($form->model(), 'settings.fields'));
        $this->assertNull(Facades\Blueprint::find('forms.test'));
    }

    #[Test]
    public function it_fires_a_deleting_event()
    {
        Event::fake();

        $form = Form::make('contact_us');
        $form->save();

        $form->delete();

        Event::assertDispatched(FormDeleting::class, function ($event) use ($form) {
            return $event->form === $form;
        });
    }

    #[Test]
    public function it_does_not_delete_when_a_deleting_event_returns_false()
    {
        Form::spy();
        Event::fake([FormDeleted::class]);

        Event::listen(FormDeleting::class, function () {
            return false;
        });

        $form = new \Statamic\Eloquent\Forms\Form('test');
        $form->handle('test');
        $form->save();

        $return = $form->delete();

        $this->assertFalse($return);
        Form::shouldNotHaveReceived('delete');
        Event::assertNotDispatched(FormDeleted::class);
    }

    #[Test]
    public function it_deletes_quietly()
    {
        Event::fake();

        $form = Form::make('contact_us');
        $form->save();

        $return = $form->deleteQuietly();

        Event::assertNotDispatched(FormDeleting::class);
        Event::assertNotDispatched(FormDeleted::class);

        $this->assertTrue($return);
    }
}
