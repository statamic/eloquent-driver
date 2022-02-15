<?php

namespace Tests\Entries;

use PHPUnit\Framework\TestCase;
use Statamic\Eloquent\Entries\Entry;
use Statamic\Eloquent\Entries\EntryModel;

class EntryTest extends TestCase
{    
    /** @test */
    public function it_loads_from_entry_model()
    {
        $model = new EntryModel([
            'slug' => 'the-slug',
            'data' => [
                'foo' => 'bar'
            ]
        ]);
        
        $entry = (new Entry)->fromModel($model);
        
        $this->assertEquals('the-slug', $entry->slug());
        $this->assertEquals('bar', $entry->data()->get('foo'));
        $this->assertEquals(['foo' => 'bar'], $entry->data()->toArray());
    }
    
    /** @test */
    public function it_saves_to_entry_model()
    {
        $model = new EntryModel([
            'slug' => 'the-slug',
            'data' => [
                'foo' => 'bar'
            ]
        ]);
        
        $entry = (new Entry)->fromModel($model);
        
        $this->assertEquals($model->toArray(), $entry->toModel()->toArray());
    }
}
