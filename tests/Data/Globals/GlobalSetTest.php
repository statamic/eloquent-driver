<?php

namespace Tests\Data\Globals;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Globals\GlobalSet;
use Tests\TestCase;

class GlobalSetTest extends TestCase
{
    #[Test]
    public function it_gets_file_contents_for_saving()
    {
        $set = (new GlobalSet)->title('The title');

        // We set the data but it's basically irrelevant since it won't get saved to this file.
        $set->in('en', function ($loc) {
            $loc->data([
                'array'  => ['first one', 'second one'],
                'string' => 'The string',
            ]);
        });

        $expected = <<<'EOT'
title: 'The title'

EOT;
        $this->assertEquals($expected, $set->fileContents());
    }
}
