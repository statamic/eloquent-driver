<?php

namespace Globals;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Events\GlobalSetSaved;
use Statamic\Events\GlobalVariablesSaved;
use Statamic\Facades;
use Tests\TestCase;

class GlobalsetTest extends TestCase
{
    #[Test]
    public function fires_events_when_globalset_saved()
    {
        Event::fake();

        $global = Facades\Globalset::make('test');

        $global->addLocalization(
            $global->makeLocalization('en')->data(['foo' => 'bar', 'baz' => 'qux'])
        );

        $global->save();

        Event::assertDispatched(GlobalSetSaved::class);
        Event::assertDispatched(GlobalVariablesSaved::class);
    }
}
