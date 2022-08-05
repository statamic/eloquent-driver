<?php

namespace Tests\Entries;

use PHPUnit\Framework\TestCase;
use Statamic\Eloquent\Entries\EntryModel;

class EntryModelTest extends TestCase
{
    /** @test */
    public function it_gets_attributes_from_json_column()
    {
        $model = new EntryModel([
            'slug' => 'the-slug',
            'data' => [
                'foo' => 'bar',
            ],
        ]);

        $this->assertEquals('the-slug', $model->slug);
        $this->assertEquals('bar', $model->foo);
        $this->assertEquals(['foo' => 'bar'], $model->data);
    }
}
