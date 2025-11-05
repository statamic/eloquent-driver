<?php

namespace Tests\Globals;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Globals\VariablesModel;
use Statamic\Facades;
use Tests\TestCase;

class GlobalVariablesTest extends TestCase
{
    #[Test]
    public function does_not_save_synced_origin_data_to_localizations()
    {
        $global = Facades\GlobalSet::make('test')->sites(['en' => null, 'fr' => 'en']);

        $global->in('en')->data(['foo' => 'bar', 'baz' => 'qux']);
        $global->in('fr')->data([]);

        $global->save();

        $this->assertCount(2, VariablesModel::all());
        $this->assertSame(VariablesModel::all()->firstWhere('locale', 'fr')->data, []);
    }
}
