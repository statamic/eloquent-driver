<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Addons\FileSettingsRepository;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Addons\SettingsRepository as SettingsRepositoryContract;
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
        Statamic::repository(SettingsRepositoryContract::class, FileSettingsRepository::class);

        Addon::all()
            ->filter(fn ($addon) => collect($addon->settings()->raw())->filter()->isNotEmpty())
            ->each(function ($addon) {
                app('statamic.eloquent.addon_settings.model')::updateOrCreate(
                    ['addon' => $addon->id()],
                    ['settings' => $addon->settings()->raw()]
                );
            });

        $this->components->info('Addon settings imported successfully.');

        return 0;
    }
}
