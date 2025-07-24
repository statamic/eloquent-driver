<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Addons\FileSettingsRepository;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Addons\SettingsRepository as SettingsRepositoryContract;
use Statamic\Eloquent\AddonSettings\AddonSettingsModel;
use Statamic\Facades\Addon;
use Statamic\Statamic;

class ExportAddonSettings extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:export-addon-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports eloquent addon settings to flat files.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Statamic::repository(SettingsRepositoryContract::class, FileSettingsRepository::class);

        AddonSettingsModel::all()->each(function ($model) {
            Addon::get($model->addon)?->settings()->set($model->settings)->save();
        });

        $this->newLine();
        $this->info('Addon settings exported');

        return 0;
    }
}
