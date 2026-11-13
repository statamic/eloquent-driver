<?php

namespace Tests\Data;

use Facades\Tests\Factories\EntryFactory;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Form;
use Statamic\Facades\FormSubmission;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Tests\TestCase;

class NaturalSortingTest extends TestCase
{
    const NAMES = ['b', 'Äpfel', 'zoo', 'apple', 'C', 'Zebra'];

    const NATURAL_ORDER = ['Äpfel', 'apple', 'b', 'C', 'Zebra', 'zoo'];

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->isUsingSqlite() && ! class_exists(\Collator::class)) {
            $this->markTestSkipped('Requires the intl extension on SQLite.');
        }

        config(['statamic.eloquent-driver.text_sorting' => 'natural']);
    }

    #[Test]
    public function it_sorts_entries_naturally()
    {
        Collection::make('posts')->save();

        foreach (self::NAMES as $i => $name) {
            EntryFactory::id((string) ($i + 1))->slug("post-{$i}")->collection('posts')->data(['title' => $name])->create();
        }

        $this->assertEquals(self::NATURAL_ORDER, Entry::query()->orderBy('title')->get()->map->get('title')->all());
        $this->assertEquals(array_reverse(self::NATURAL_ORDER), Entry::query()->orderByDesc('title')->get()->map->get('title')->all());
    }

    #[Test]
    public function it_sorts_terms_naturally()
    {
        Taxonomy::make('tags')->save();

        foreach (self::NAMES as $i => $name) {
            Term::make("term-{$i}")->taxonomy('tags')->data(['title' => $name])->save();
        }

        $this->assertEquals(self::NATURAL_ORDER, Term::query()->orderBy('title')->get()->map->get('title')->all());
    }

    #[Test]
    public function it_sorts_submissions_naturally()
    {
        $form = tap(Form::make('test'))->save();

        foreach (self::NAMES as $name) {
            FormSubmission::make()->form($form)->data(['name' => $name])->save();
        }

        $this->assertEquals(self::NATURAL_ORDER, FormSubmission::query()->orderBy('name')->get()->map->get('name')->all());
    }

    #[Test]
    public function it_sorts_assets_naturally()
    {
        Storage::fake('test');
        AssetContainer::make('test')->disk('test')->save();

        foreach (self::NAMES as $name) {
            Storage::disk('test')->put("{$name}.jpg", '');
            Asset::make()->container('test')->path("{$name}.jpg")->set('title', $name)->save();
        }

        $query = fn () => Asset::query()->where('container', 'test');

        $this->assertEquals(self::NATURAL_ORDER, $query()->orderBy('basename')->get()->map->filename()->all());
        $this->assertEquals(self::NATURAL_ORDER, $query()->orderBy('title')->get()->map->get('title')->all());
    }
}
