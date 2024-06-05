<?php

namespace Tests\Forms;

use Carbon\Carbon;
use Statamic\Eloquent\Forms\FormModel;
use Statamic\Eloquent\Forms\SubmissionModel;
use Tests\TestCase;

class FormSubmissionTest extends TestCase
{
    /** @test */
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

    /** @test */
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

    /** @test */
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
}
