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
}
