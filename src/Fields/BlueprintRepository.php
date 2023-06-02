<?php

namespace Statamic\Eloquent\Fields;

use Statamic\Facades\Blink;
use Statamic\Fields\Blueprint;
use Statamic\Fields\BlueprintRepository as StacheRepository;
use Statamic\Support\Arr;

class BlueprintRepository extends StacheRepository
{
    protected const BLINK_FOUND = 'blueprints.found';
    protected const BLINK_FROM_FILE = 'blueprints.from-file';
    protected const BLINK_NAMESPACE_PATHS = 'blueprints.paths-in-namespace';

    public function find($blueprint): ?Blueprint
    {
        return Blink::store(self::BLINK_FOUND)->once($blueprint, function () use ($blueprint) {
            [$namespace, $handle] = $this->getNamespaceAndHandle($blueprint);
            if (! $blueprint) {
                return null;
            }

            $blueprintModel = ($namespace ? $this->filesIn($namespace) : BlueprintModel::whereNull('namespace'))
                ->where('handle', $handle)
                ->first();

            if (! $blueprintModel) {
                throw_if(
                    $namespace === null && $handle === 'default',
                    Exception::class,
                    'Default Blueprint is required but not found. '
                );

                return null;
            }

            return $this->makeBlueprintFromModel($blueprintModel) ?? $this->findFallback($blueprint);
        });
    }

    public function save(Blueprint $blueprint)
    {
        $this->clearBlinkCaches();

        $this->updateModel($blueprint);
    }

    public function delete(Blueprint $blueprint)
    {
        $this->clearBlinkCaches();

        $this->deleteModel($blueprint);
    }

    private function clearBlinkCaches()
    {
        Blink::store(self::BLINK_FOUND)->flush();
        Blink::store(self::BLINK_FROM_FILE)->flush();
        Blink::store(self::BLINK_NAMESPACE_PATHS)->flush();
    }

    public function in(string $namespace)
    {
        return $this
            ->filesIn($namespace)
            ->map(function ($file) {
                return $this->makeBlueprintFromModel($file);
            })
            ->sort(function ($a, $b) {
                $orderA = $a->order() ?? 99999;
                $orderB = $b->order() ?? 99999;

                return $orderA === $orderB
                    ? $a->title() <=> $b->title()
                    : $orderA <=> $orderB;
            })
            ->keyBy->handle();
    }

    protected function filesIn($namespace)
    {
        return Blink::store(self::BLINK_NAMESPACE_PATHS)->once($namespace ?? 'none', function () use ($namespace) {
            $namespace = str_replace('/', '.', $namespace);

            if (count($blueprintModels = BlueprintModel::where('namespace', $namespace)->get()) == 0) {
                return collect();
            }

            return $blueprintModels;
        });
    }

    private function makeBlueprintFromModel($model)
    {
        return Blink::store(self::BLINK_FROM_FILE)->once('database:blueprints:'.$model->id, function () use ($model) {
            return Blueprint::make()
                ->setHidden(Arr::get($model->data, 'hide'))
                ->setOrder(Arr::get($model->data, 'order'))
                ->setHandle($model->handle)
                ->setNamespace($model->namespace)
                ->setContents($this->updateOrderFromBlueprintSections($model->data));
        });
    }

    protected function getNamespaceAndHandle($blueprint)
    {
        $blueprint = str_replace('/', '.', $blueprint);
        $parts = explode('.', $blueprint);
        $handle = array_pop($parts);
        $namespace = implode('.', $parts);
        $namespace = empty($namespace) ? null : $namespace;

        return [$namespace, $handle];
    }

    public function updateModel($blueprint)
    {
        $model = app('statamic.eloquent.blueprints.blueprint_model')::firstOrNew([
            'handle'    => $blueprint->handle(),
            'namespace' => $blueprint->namespace() ?? null,
        ]);

        $model->data = $this->addOrderToBlueprintSections($blueprint->contents());
        $model->save();
    }

    public function deleteModel($blueprint)
    {
        $model = app('statamic.eloquent.blueprints.blueprint_model')::where('namespace', $blueprint->namespace() ?? null)
            ->where('handle', $blueprint->handle())
            ->first();

        if ($model) {
            $model->delete();
        }
    }

    private function addOrderToBlueprintSections($contents)
    {
        $count = 0;
        $contents['tabs'] = collect($contents['tabs'] ?? [])
            ->map(function ($tab) use (&$count) {
                $tab['__count'] = $count++;

                if (isset($tab['sections']) && is_array($tab['sections'])) {
                    $sectionCount = 0;
                    $tab['sections'] = collect($tab['sections'])
                        ->map(function ($section) use (&$sectionCount) {
                            $section['__count'] = $sectionCount++;

                            return $section;
                        });
                }

                return $tab;
            })
            ->toArray();

        return $contents;
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
