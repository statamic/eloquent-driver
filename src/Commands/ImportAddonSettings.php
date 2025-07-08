<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Extend\AddonSettingsRepository as AddonSettingsRepositoryContract;
use Statamic\Extend\AddonSettingsRepository as FileAddonSettingsRepository;
use Statamic\Facades\Addon;
use Statamic\Statamic;

class ImportAddonSettings extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:import-addon-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file-based addon settings into the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Statamic::repository(AddonSettingsRepositoryContract::class, FileAddonSettingsRepository::class);

        Addon::all()->each(function ($addon) {
            app('statamic.eloquent.addon_settings.model')::updateOrCreate(
                ['addon' => $addon->id()],
                ['settings' => $addon->settings()->rawValues()]
            );
        });

        $this->components->info('Addon settings imported successfully.');

        return 0;
    }
}
