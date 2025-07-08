<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Extend\AddonSettingsRepository as AddonSettingsRepositoryContract;
use Statamic\Eloquent\AddonSettings\AddonSettingsModel;
use Statamic\Extend\AddonSettingsRepository as FileAddonSettingsRepository;
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
        Statamic::repository(AddonSettingsRepositoryContract::class, FileAddonSettingsRepository::class);

        AddonSettingsModel::all()
            ->each(function ($model) {
                Addon::get($model->addon)?->settings()->values($model->settings)->save();
            });

        $this->newLine();
        $this->info('Addon settings exported');

        return 0;
    }
}
