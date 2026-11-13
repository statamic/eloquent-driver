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

class AlphabeticalSortingTest extends TestCase
{
    const NAMES = ['b', 'Äpfel', 'zoo', 'apple', 'C', 'Zebra'];

    const ALPHABETICAL_ORDER = ['Äpfel', 'apple', 'b', 'C', 'Zebra', 'zoo'];

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->isUsingSqlite() && ! class_exists(\Collator::class)) {
            $this->markTestSkipped('Requires the intl extension on SQLite.');
        }

        config(['statamic.eloquent-driver.alphabetical_sorting' => true]);
    }

    #[Test]
    public function it_sorts_entries_alphabetically()
    {
        Collection::make('posts')->save();

        foreach (self::NAMES as $i => $name) {
            EntryFactory::id((string) ($i + 1))->slug("post-{$i}")->collection('posts')->data(['title' => $name])->create();
        }

        $this->assertEquals(self::ALPHABETICAL_ORDER, Entry::query()->orderBy('title')->get()->map->get('title')->all());
        $this->assertEquals(array_reverse(self::ALPHABETICAL_ORDER), Entry::query()->orderByDesc('title')->get()->map->get('title')->all());
    }

    #[Test]
    public function it_sorts_terms_alphabetically()
    {
        Taxonomy::make('tags')->save();

        foreach (self::NAMES as $i => $name) {
            Term::make("term-{$i}")->taxonomy('tags')->data(['title' => $name])->save();
        }

        $this->assertEquals(self::ALPHABETICAL_ORDER, Term::query()->orderBy('title')->get()->map->get('title')->all());
    }

    #[Test]
    public function it_sorts_submissions_alphabetically()
    {
        $form = tap(Form::make('test'))->save();

        foreach (self::NAMES as $name) {
            FormSubmission::make()->form($form)->data(['name' => $name])->save();
        }

        $this->assertEquals(self::ALPHABETICAL_ORDER, FormSubmission::query()->orderBy('name')->get()->map->get('name')->all());
    }

    #[Test]
    public function it_sorts_assets_alphabetically()
    {
        Storage::fake('test');
        AssetContainer::make('test')->disk('test')->save();

        foreach (self::NAMES as $name) {
            Storage::disk('test')->put("{$name}.jpg", '');
            Asset::make()->container('test')->path("{$name}.jpg")->set('title', $name)->save();
        }

        $query = fn () => Asset::query()->where('container', 'test');

        $this->assertEquals(self::ALPHABETICAL_ORDER, $query()->orderBy('basename')->get()->map->filename()->all());
        $this->assertEquals(self::ALPHABETICAL_ORDER, $query()->orderBy('title')->get()->map->get('title')->all());
    }
}
