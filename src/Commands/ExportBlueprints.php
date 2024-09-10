<?php

namespace Statamic\Eloquent\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Eloquent\Fields\BlueprintModel;
use Statamic\Eloquent\Fields\FieldsetModel;
use Statamic\Facades\Blueprint as BlueprintFacade;
use Statamic\Fields\Blueprint as StacheBlueprint;
use Statamic\Fields\Fieldset as StacheFieldset;
use Statamic\Support\Arr;
use Statamic\Support\Str;

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
            $this->exportFieldsets();
        });

        return 0;
    }

    private function usingDefaultRepositories(Closure $callback)
    {
        Facade::clearResolvedInstance(\Statamic\Fields\BlueprintRepository::class);
        Facade::clearResolvedInstance(\Statamic\Fields\FieldsetRepository::class);

        app()->bind(\Statamic\Fields\BlueprintRepository::class, function () {
            return (new \Statamic\Fields\BlueprintRepository)
                ->setDirectory(resource_path('blueprints'));
        });

        app()->singleton(\Statamic\Fields\FieldsetRepository::class, function () {
            return (new \Statamic\Fields\FieldsetRepository)
                ->setDirectory(resource_path('fieldsets'));
        });

        $callback();
    }

    private function exportBlueprints()
    {
        $this->withProgressBar(BlueprintModel::all(), function ($model) {
            if (! $model->namespace) {
                return;
            }

            (new StacheBlueprint)
                ->setHandle($model->handle)
                ->setHidden(Arr::get($model->data, 'hide'))
                ->setOrder(Arr::get($model->data, 'order'))
                ->setNamespace($this->getBlueprintNamespace($model->namespace))
                ->setContents($this->updateOrderFromBlueprintSections($model->data))
                ->save();
        });

        $this->newLine();
        $this->info('Blueprints exported');
    }

    private function exportFieldsets()
    {
        $this->withProgressBar(FieldsetModel::all(), function ($model) {
            if (! $model->handle) {
                return;
            }

            (new StacheFieldset)
                ->setHandle($model->handle)
                ->setContents($model->data)
                ->save();
        });

        $this->newLine();
        $this->info('Fieldsets exported');
    }

    private function getBlueprintNamespace(string $namespace): string
    {
        $blueprintDirectory = str_replace('\\', '/', BlueprintFacade::directory());
        $blueprintDirectory = str_replace('/', '.', $blueprintDirectory);

        if (Str::startsWith($namespace, $blueprintDirectory)) {
            return mb_substr($namespace, mb_strlen($blueprintDirectory));
        }

        return $namespace;
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
