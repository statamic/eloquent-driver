<?php

namespace Globals;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Globals\VariablesModel;
use Statamic\Events\GlobalSetSaved;
use Statamic\Events\GlobalVariablesSaved;
use Statamic\Facades;
use Tests\TestCase;

class GlobalVariablesTest extends TestCase
{
    #[Test]
    public function does_not_save_synced_origin_data_to_localizations()
    {
        $global = Facades\Globalset::make('test');

        $global->addLocalization($global->makeLocalization('en')->data(['foo' => 'bar', 'baz' => 'qux']));
        $global->addLocalization($global->makeLocalization('fr')->origin('en')->data([]));

        $global->save();

        $this->assertCount(2, VariablesModel::all());
        $this->assertSame(VariablesModel::all()->firstWhere('locale', 'fr')->data, []);
    }
}
