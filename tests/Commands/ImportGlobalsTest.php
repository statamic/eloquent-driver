<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Contracts\Globals\GlobalRepository as GlobalRepositoryContract;
use Statamic\Contracts\Globals\GlobalSet as GlobalSetContract;
use Statamic\Contracts\Globals\GlobalVariablesRepository as GlobalVariablesRepositoryContract;
use Statamic\Contracts\Globals\Variables as VariablesContract;
use Statamic\Eloquent\Globals\GlobalSetModel;
use Statamic\Eloquent\Globals\VariablesModel;
use Statamic\Facades\GlobalSet;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportGlobalsTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function setUp(): void
    {
        parent::setUp();

        Facade::clearResolvedInstance(GlobalRepositoryContract::class);
        Facade::clearResolvedInstance(GlobalVariablesRepositoryContract::class);

        app()->bind(GlobalSetContract::class, \Statamic\Globals\GlobalSet::class);
        app()->bind(VariablesContract::class, \Statamic\Globals\Variables::class);
        app()->bind(GlobalRepositoryContract::class, \Statamic\Stache\Repositories\GlobalRepository::class);
        app()->bind(GlobalVariablesRepositoryContract::class, \Statamic\Stache\Repositories\GlobalVariablesRepository::class);
    }

    #[Test]
    public function it_imports_global_sets_and_variables()
    {
        $globalSet = tap(GlobalSet::make('footer')->title('Footer'))->save();
        $variables = $globalSet->makeLocalization('en')->data(['foo' => 'bar']);
        $globalSet->addLocalization($variables)->save();

        $this->assertCount(0, GlobalSetModel::all());
        $this->assertCount(0, VariablesModel::all());

        $this->artisan('statamic:eloquent:import-globals')
            ->expectsQuestion('Do you want to import global sets?', true)
            ->expectsQuestion('Do you want to import global variables?', true)
            ->expectsOutputToContain('Globals imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, GlobalSetModel::all());
        $this->assertCount(1, VariablesModel::all());

        $this->assertDatabaseHas('global_sets', ['handle' => 'footer', 'title' => 'Footer']);
        $this->assertDatabaseHas('global_set_variables', ['handle' => 'footer', 'locale' => 'en', 'data' => '{"foo":"bar"}']);
    }

    #[Test]
    public function it_imports_global_sets_and_variables_with_force_argument()
    {
        $globalSet = tap(GlobalSet::make('footer')->title('Footer'))->save();
        $variables = $globalSet->makeLocalization('en')->data(['foo' => 'bar']);
        $globalSet->addLocalization($variables)->save();

        $this->assertCount(0, GlobalSetModel::all());
        $this->assertCount(0, VariablesModel::all());

        $this->artisan('statamic:eloquent:import-globals', ['--force' => true])
            ->expectsOutputToContain('Globals imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, GlobalSetModel::all());
        $this->assertCount(1, VariablesModel::all());

        $this->assertDatabaseHas('global_sets', ['handle' => 'footer', 'title' => 'Footer']);
        $this->assertDatabaseHas('global_set_variables', ['handle' => 'footer', 'locale' => 'en', 'data' => '{"foo":"bar"}']);
    }

    #[Test]
    public function it_imports_only_global_sets_with_console_question()
    {
        $globalSet = tap(GlobalSet::make('footer')->title('Footer'))->save();
        $variables = $globalSet->makeLocalization('en')->data(['foo' => 'bar']);
        $globalSet->addLocalization($variables)->save();

        $this->assertCount(0, GlobalSetModel::all());
        $this->assertCount(0, VariablesModel::all());

        $this->artisan('statamic:eloquent:import-globals')
            ->expectsQuestion('Do you want to import global sets?', true)
            ->expectsQuestion('Do you want to import global variables?', false)
            ->expectsOutputToContain('Globals imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, GlobalSetModel::all());
        $this->assertCount(0, VariablesModel::all());

        $this->assertDatabaseHas('global_sets', ['handle' => 'footer', 'title' => 'Footer']);
        $this->assertDatabaseMissing('global_set_variables', ['handle' => 'footer', 'locale' => 'en', 'data' => '{"foo":"bar"}']);
    }

    #[Test]
    public function it_imports_only_global_sets_with_only_global_sets_argument()
    {
        $globalSet = tap(GlobalSet::make('footer')->title('Footer'))->save();
        $variables = $globalSet->makeLocalization('en')->data(['foo' => 'bar']);
        $globalSet->addLocalization($variables)->save();

        $this->assertCount(0, GlobalSetModel::all());
        $this->assertCount(0, VariablesModel::all());

        $this->artisan('statamic:eloquent:import-globals', ['--only-global-sets' => true])
            ->expectsOutputToContain('Globals imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, GlobalSetModel::all());
        $this->assertCount(0, VariablesModel::all());

        $this->assertDatabaseHas('global_sets', ['handle' => 'footer', 'title' => 'Footer']);
        $this->assertDatabaseMissing('global_set_variables', ['handle' => 'footer', 'locale' => 'en', 'data' => '{"foo":"bar"}']);
    }

    #[Test]
    public function it_imports_only_variables_with_console_question()
    {
        $globalSet = tap(GlobalSet::make('footer')->title('Footer'))->save();
        $variables = $globalSet->makeLocalization('en')->data(['foo' => 'bar']);
        $globalSet->addLocalization($variables)->save();

        $this->assertCount(0, GlobalSetModel::all());
        $this->assertCount(0, VariablesModel::all());

        $this->artisan('statamic:eloquent:import-globals')
            ->expectsQuestion('Do you want to import global sets?', false)
            ->expectsQuestion('Do you want to import global variables?', true)
            ->expectsOutputToContain('Globals imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(0, GlobalSetModel::all());
        $this->assertCount(1, VariablesModel::all());

        $this->assertDatabaseMissing('global_sets', ['handle' => 'footer', 'title' => 'Footer']);
        $this->assertDatabaseHas('global_set_variables', ['handle' => 'footer', 'locale' => 'en', 'data' => '{"foo":"bar"}']);
    }

    #[Test]
    public function it_imports_only_variables_with_only_global_variables_argument()
    {
        $globalSet = tap(GlobalSet::make('footer')->title('Footer'))->save();
        $variables = $globalSet->makeLocalization('en')->data(['foo' => 'bar']);
        $globalSet->addLocalization($variables)->save();

        $this->assertCount(0, GlobalSetModel::all());
        $this->assertCount(0, VariablesModel::all());

        $this->artisan('statamic:eloquent:import-globals', ['--only-global-variables' => true])
            ->expectsOutputToContain('Globals imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(0, GlobalSetModel::all());
        $this->assertCount(1, VariablesModel::all());

        $this->assertDatabaseMissing('global_sets', ['handle' => 'footer', 'title' => 'Footer']);
        $this->assertDatabaseHas('global_set_variables', ['handle' => 'footer', 'locale' => 'en', 'data' => '{"foo":"bar"}']);
    }
}
