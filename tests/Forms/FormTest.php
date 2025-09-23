<?php

namespace Tests\Forms;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades;
use Statamic\Support\Arr;
use Tests\TestCase;

class FormTest extends TestCase
{
    #[Test]
    public function finding_a_form_sets_the_blink_cache()
    {
        Facades\Form::make('test')->title('Test form')->save();

        $form = Facades\Form::find('test');

        $this->assertSame(Facades\Blink::get('eloquent-forms-test'), $form);
    }

    #[Test]
    public function getting_all_forms_sets_the_blink_cache()
    {
        $form = tap(Facades\Form::make('test')->title('Test form'))->save();

        Facades\Form::all();

        $this->assertCount(1, Facades\Blink::get('eloquent-forms'));
        $this->assertSame($form->handle(), Facades\Blink::get('eloquent-forms')->first()->handle());
    }

    #[Test]
    public function saving_a_form_removes_the_blink_cache()
    {
        Facades\Form::make('test')->title('Test form')->save();

        $form = Facades\Form::find('test');
        Facades\Form::all(); // to set up eloquent-forms blink

        $this->assertSame(Facades\Blink::get('eloquent-forms-test'), $form);
        $this->assertCount(1, Facades\Blink::get('eloquent-forms'));

        $form->save();

        $this->assertNull(Facades\Blink::get('eloquent-forms-test'));
        $this->assertNull(Facades\Blink::get('eloquent-forms'));
    }

    #[Test]
    public function deleting_a_form_removes_the_blink_cache()
    {
        Facades\Form::make('test')->title('Test form')->save();

        $form = Facades\Form::find('test');
        Facades\Form::all(); // to set up eloquent-forms blink

        $this->assertSame(Facades\Blink::get('eloquent-forms-test'), $form);
        $this->assertCount(1, Facades\Blink::get('eloquent-forms'));

        $form->delete();

        $this->assertNull(Facades\Blink::get('eloquent-forms-test'));
        $this->assertNull(Facades\Blink::get('eloquent-forms'));
    }

    #[Test]
    public function it_stores_form_data()
    {
        $form = tap(Facades\Form::make('test')->title('Test form')->data(['some' => 'data']))->save();

        $this->assertSame(['some' => 'data'], Arr::get($form->model(), 'settings.data'));
    }

    #[Test]
    public function null_values_are_removed_from_data()
    {
        $form = tap(Facades\Form::make('test')->title('Test form')->data(['some' => 'data', 'null_value' => null]))->save();

        $this->assertSame(['some' => 'data'], Arr::get($form->model(), 'settings.data'));
    }
}
