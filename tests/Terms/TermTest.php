<?php

namespace Tests\Terms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Taxonomies\Taxonomy;
use Statamic\Eloquent\Taxonomies\TermModel;
use Statamic\Facades\Term as TermFacade;
use Tests\TestCase;

class TermTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_doesnt_create_a_new_model_when_slug_is_changed()
    {
        Taxonomy::make('test')->title('test')->save();

        $term = tap(TermFacade::make('test-term')->taxonomy('test')->data([]))->save();

        $this->assertCount(1, TermModel::all());
        $this->assertSame('test-term', TermModel::first()->slug);

        $term->slug('new-slug');
        $term->save();

        $this->assertCount(1, TermModel::all());
        $this->assertSame('new-slug', TermModel::first()->slug);
    }

    #[Test]
    public function null_values_are_removed_from_data()
    {
        Taxonomy::make('test')->title('test')->save();

        $term = tap(TermFacade::make('test-term')->taxonomy('test')->data(['null_value' => null]))->save();

        $this->assertArrayNotHasKey('null_value', $term->model()->data);
    }

    #[Test]
    public function it_saves_updated_at_value_correctly()
    {
        $this->freezeSecond();

        Taxonomy::make('test')->title('test')->save();

        tap(TermFacade::make('test-term')->taxonomy('test')->data([]))->save();

        /** @var LocalizedTerm $term */
        $term = TermFacade::query()->first();
        $term->set('foo', 'bar');
        $term->save();

        $this->assertEquals(now(), $term->updated_at);
        $this->assertEquals(now(), TermFacade::query()->first()->updated_at);
    }
}
