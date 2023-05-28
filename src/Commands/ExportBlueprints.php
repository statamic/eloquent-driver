<?php

namespace Statamic\Eloquent\Commands;

use Closure;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Eloquent\Fields\BlueprintModel;
use Statamic\Eloquent\Fields\FieldsetModel;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Fieldset;
use Statamic\Support\Arr;

class ExportBlueprints extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:export-blueprints';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export eloquent based blueprints and fieldsets to flat files.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->usingDefaultRepositories(function () {
            $this->exportBlueprints();
            $this->importFieldsets();
        });

        return 0;
    }

    private function usingDefaultRepositories(Closure $callback)
    {
        app()->bind(\Statamic\Fields\BlueprintRepository::class, function () {
            return (new \Statamic\Fields\BlueprintRepository)
                ->setDirectory(resource_path('blueprints'));
        });

        app()->singleton(
            'Statamic\Fields\FieldsetRepository',
            'Statamic\Fields\FieldsetRepository'
        );

        $callback();
    }

    private function exportBlueprints()
    {
        $this->withProgressBar(BlueprintModel::all(), function ($model) {
            if (! $model->namespace) {
                return;
            }

            Blueprint::make()
                ->setHidden(Arr::get($model->data, 'hide'))
                ->setOrder(Arr::get($model->data, 'order'))
                ->setHandle($model->handle)
                ->setNamespace($model->namespace)
                ->setContents($this->updateOrderFromBlueprintSections($model->data))
                ->save();
        });

        $this->newLine();
        $this->info('Blueprints exported');
    }

    private function importFieldsets()
    {
        $this->withProgressBar(FieldsetModel::all(), function ($model) {
            Fieldset::make()
                ->setHandle($model->handle)
                ->setContents($model->data)
                ->save();
        });

        $this->newLine();
        $this->info('Fieldsets exported');
    }

    private function updateOrderFromBlueprintSections($contents)
    {
        $contents['tabs'] = collect($contents['tabs'] ?? [])
            ->sortBy('__count')
            ->map(function ($tab) {
                unset($tab['__count']);

                if (isset($tab['sections']) && is_array($tab['sections'])) {
                    $tab['sections'] = collect($tab['sections'])
                        ->sortBy('__count')
                        ->map(function ($section) use (&$sectionCount) {
                            unset($section['__count']);

                            return $section;
                        })
                        ->toArray();
                }

                return $tab;
            })
            ->toArray();

        return $contents;
    }
}