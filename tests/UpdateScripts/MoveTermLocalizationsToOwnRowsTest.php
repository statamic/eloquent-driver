<?php

namespace Tests\UpdateScripts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Updates\MoveTermLocalizationsToOwnRows;
use Statamic\Facades\Blink;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Testing\Concerns\RunsUpdateScripts;
use Tests\TestCase;

class MoveTermLocalizationsToOwnRowsTest extends TestCase
{
    use RefreshDatabase, RunsUpdateScripts;

    protected function setUp(): void
    {
        parent::setUp();

        // Put the table back into its pre-update shape, where localizations lived in the data column.
        Schema::table('taxonomy_terms', function ($table) {
            $table->dropIndex(['origin']);
            $table->dropColumn('origin');
        });
    }

    private function migration()
    {
        return require __DIR__.'/../../database/migrations/updates/move_term_localizations_to_own_rows.php.stub';
    }

    private function insertLegacyTerm(string $slug, array $data): void
    {
        DB::table('taxonomy_terms')->insert([
            'site' => 'en',
            'slug' => $slug,
            'uri' => "/tags/{$slug}",
            'taxonomy' => 'tags',
            'data' => json_encode($data),
        ]);
    }

    #[Test]
    public function it_is_registered()
    {
        $this->assertUpdateScriptRegistered(MoveTermLocalizationsToOwnRows::class);
    }

    #[Test]
    public function it_only_updates_when_the_origin_column_is_missing()
    {
        $script = new MoveTermLocalizationsToOwnRows('statamic/eloquent-driver');

        $this->assertTrue($script->shouldUpdate('5.14.0', '5.13.0'));

        $this->migration()->up();

        $this->assertFalse($script->shouldUpdate('5.14.0', '5.13.0'));
    }

    #[Test]
    public function the_migration_moves_localizations_into_rows_and_back()
    {
        $this->setSites([
            'en' => ['url' => 'http://localhost/', 'locale' => 'en_US', 'name' => 'English'],
            'fr' => ['url' => 'http://localhost/fr/', 'locale' => 'fr_FR', 'name' => 'French'],
        ]);

        Taxonomy::make('tags')->sites(['en', 'fr'])->save();

        $this->insertLegacyTerm('a', ['title' => 'A', 'localizations' => ['en' => ['title' => 'A'], 'fr' => ['title' => 'A fr']]]);
        $this->insertLegacyTerm('b', ['title' => 'B']);

        $this->migration()->up();

        $rows = DB::table('taxonomy_terms')->orderBy('id')->get();
        $this->assertCount(3, $rows);

        $canonical = $rows->firstWhere('slug', 'a');
        $this->assertNull($canonical->origin);
        $this->assertEquals(['title' => 'A'], json_decode($canonical->data, true));

        $fr = $rows->where('slug', 'a')->firstWhere('site', 'fr');
        $this->assertEquals($canonical->id, $fr->origin);
        $this->assertEquals('/tags/a', $fr->uri);
        $this->assertEquals(['title' => 'A fr'], json_decode($fr->data, true));

        Blink::flush();
        $this->assertEquals('A fr', Term::find('tags::a')->in('fr')->get('title'));
        $this->assertEquals(['fr'], Term::query()->where('site', 'fr')->where('slug', 'a')->get()->map->locale()->all());

        $this->migration()->down();

        $this->assertFalse(Schema::hasColumn('taxonomy_terms', 'origin'));
        $this->assertEquals(2, DB::table('taxonomy_terms')->count());
        $this->assertEquals(
            ['title' => 'A', 'localizations' => ['en' => ['title' => 'A'], 'fr' => ['title' => 'A fr']]],
            json_decode(DB::table('taxonomy_terms')->where('slug', 'a')->value('data'), true)
        );
    }
}
