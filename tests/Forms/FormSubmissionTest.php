<?php

namespace Tests\Forms;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Forms\FormModel;
use Statamic\Eloquent\Forms\SubmissionModel;
use Statamic\Events\SubmissionCreated;
use Statamic\Events\SubmissionDeleted;
use Statamic\Events\SubmissionSaved;
use Statamic\Facades;
use Tests\TestCase;

class FormSubmissionTest extends TestCase
{
    #[Test]
    public function it_should_have_timestamps()
    {
        $form = FormModel::create([
            'handle' => 'test',
            'title' => 'Test',
        ]);

        $submission = SubmissionModel::create([
            'id' => 1111111111.1111,
            'form' => $form->handle,
            'data' => [
                'name' => 'John Doe',
            ],
        ]);

        $this->assertInstanceOf(Carbon::class, $submission->created_at);
        $this->assertInstanceOf(Carbon::class, $submission->updated_at);
    }

    #[Test]
    public function it_should_save_to_the_database()
    {
        $form = FormModel::create([
            'handle' => 'test',
            'title' => 'Test',
        ]);

        $submission = SubmissionModel::create([
            'id' => 1111111111.1111,
            'form' => $form->handle,
            'data' => [
                'name' => 'John Doe',
            ],
        ]);

        $this->assertDatabaseHas('form_submissions', [
            'id' => $submission->id,
            'form' => $form->handle,
            'data' => json_encode([
                'name' => 'John Doe',
            ]),
        ]);
    }

    #[Test]
    public function it_should_not_overwrite_submissions()
    {
        $form = FormModel::create([
            'handle' => 'test',
            'title' => 'Test',
        ]);

        $submission = SubmissionModel::create([
            'id' => 1111111111.1111,
            'form' => $form->handle,
            'data' => [
                'name' => 'John Doe',
            ],
        ]);

        $submission = SubmissionModel::create([
            'id' => 1111111111.2222,
            'form' => $form->handle,
            'data' => [
                'name' => 'Billy Doe',
            ],
        ]);

        $this->assertCount(2, SubmissionModel::all());
    }

    #[Test]
    public function it_should_not_save_date_in_data()
    {
        $form = tap(Facades\Form::make('test')->title('Test'))
            ->save();

        $submission = tap($form->makeSubmission([
            'name' => 'John Doe',
        ]))->save();

        $this->assertInstanceOf(Carbon::class, $submission->date());
        $this->assertArrayNotHasKey('date', $submission->model()->data);

        $fresh = \Statamic\Eloquent\Forms\Submission::fromModel($submission->model()->fresh());

        $this->assertInstanceOf(Carbon::class, $fresh->date());
        $this->assertSame($fresh->date()->format('u'), $submission->date()->format('u'));
    }

    #[Test]
    public function null_values_are_removed_from_data()
    {
        $form = tap(Facades\Form::make('test')->title('Test'))
            ->save();

        $submission = tap($form->makeSubmission([
            'name' => 'John Doe',
            'null_value' => null,
        ]))->save();

        $this->assertArrayNotHasKey('null_value', $submission->model()->data);
    }

    #[Test]
    public function it_should_save_quietly()
    {
        $form = tap(Facades\Form::make('test')->title('Test'))
            ->save();

        Event::fake();

        tap($form->makeSubmission([
            'name' => 'John Doe',
        ]))->saveQuietly();

        Event::assertNotDispatched(SubmissionSaved::class);
        Event::assertNotDispatched(SubmissionCreated::class);

        tap($form->makeSubmission([
            'name' => 'John Doe',
        ]))->save();

        Event::assertDispatched(SubmissionSaved::class);
        Event::assertDispatched(SubmissionCreated::class);
    }

    #[Test]
    public function it_should_delete_quietly()
    {
        $form = tap(Facades\Form::make('test')->title('Test'))
            ->save();

        Event::fake();

        $submission = tap($form->makeSubmission([
            'name' => 'John Doe',
        ]))->save();

        $result = $submission->deleteQuietly();

        Event::assertNotDispatched(SubmissionDeleted::class);
        $this->assertSame($result, true);

        $submission = tap($form->makeSubmission([
            'name' => 'John Doe',
        ]))->save();

        $submission->delete();

        Event::assertDispatched(SubmissionDeleted::class);
        $this->assertSame($result, true);
    }
}
