<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\Facade;
use Statamic\Contracts\Globals\GlobalRepository as GlobalRepositoryContract;
use Statamic\Contracts\Globals\GlobalSet as GlobalSetContract;
use Statamic\Contracts\Globals\GlobalVariablesRepository as GlobalVariablesRepositoryContract;
use Statamic\Contracts\Globals\Variables as VariablesContract;
use Statamic\Eloquent\Globals\GlobalSetModel;
use Statamic\Eloquent\Globals\VariablesModel;
use Tests\PreventSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportGlobalsTest extends TestCase
{
    use PreventSavingStacheItemsToDisk;

    public function setUp(): void
    {
        parent::setUp();

        Facade::clearResolvedInstance(GlobalRepositoryContract::class);
        Facade::clearResolvedInstance(GlobalVariablesRepositoryContract::class);

        app()->bind(GlobalSetContract::class, \Statamic\Globals\GlobalSet::class);
        app()->bind(VariablesContract::class, \Statamic\Globals\Variables::class);
        app()->bind(GlobalRepositoryContract::class, \Statamic\Stache\Repositories\GlobalRepository::class);
        app()->bind(GlobalVariablesRepositoryContract::class, \Statamic\Stache\Repositories\GlobalVariablesRepository::class);
    }

    /** @test */
    public function it_imports_global_sets_and_variables()
    {
        $globalSet = tap(\Statamic\Facades\GlobalSet::make('footer')->title('Footer'))->save();
        $globalSet->makeLocalization('en')->data(['foo' => 'bar'])->save();

        $this->assertCount(0, GlobalSetModel::all());
        $this->assertCount(0, VariablesModel::all());

        $this->artisan('statamic:eloquent:import-globals')
            ->expectsOutput('Globals imported')
            ->assertExitCode(0);

        $this->assertCount(1, GlobalSetModel::all());
        $this->assertCount(1, VariablesModel::all());
    }

    /** @test */
    public function it_imports_only_global_sets()
    {
        $globalSet = tap(\Statamic\Facades\GlobalSet::make('footer')->title('Footer'))->save();
        $globalSet->makeLocalization('en')->data(['foo' => 'bar'])->save();

        $this->assertCount(0, GlobalSetModel::all());
        $this->assertCount(0, VariablesModel::all());

        $this->artisan('statamic:eloquent:import-globals', ['--only-global-sets' => true])
            ->expectsOutput('Globals imported')
            ->assertExitCode(0);

        $this->assertCount(1, GlobalSetModel::all());
        $this->assertCount(0, VariablesModel::all());
    }

    /** @test */
    public function it_imports_only_variables()
    {
        $globalSet = tap(\Statamic\Facades\GlobalSet::make('footer')->title('Footer'))->save();
        $globalSet->makeLocalization('en')->data(['foo' => 'bar'])->save();

        $this->assertCount(0, GlobalSetModel::all());
        $this->assertCount(0, VariablesModel::all());

        $this->artisan('statamic:eloquent:import-globals', ['--only-global-variables' => true])
            ->expectsOutput('Globals imported')
            ->assertExitCode(0);

        $this->assertCount(0, GlobalSetModel::all());
        $this->assertCount(1, VariablesModel::all());
    }
}
