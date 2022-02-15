<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Support\Collection;
use Statamic\Contracts\Assets\AssetContainer as AssetContainerContract;
use Statamic\Stache\Repositories\AssetContainerRepository as StacheRepository;

class AssetContainerRepository extends StacheRepository
{
    protected $store;

    public function all(): Collection
    {
        return app('statamic.eloquent.assets.container-model')::all()
            ->map(function($model) {
                return app(AssetContainerContract::class)->fromModel($model);
            });
    }

    public function findByHandle(string $handle): ?AssetContainerContract
    {
        $model = app('statamic.eloquent.assets.container-model')::whereHandle($handle)->first();

        if (! $model) {
            return null;
        }

        return app(AssetContainerContract::class)->fromModel($model);
    }

    public function make(string $handle = null): AssetContainerContract
    {
        return app(AssetContainerContract::class)->handle($handle);
    }

    public function save(AssetContainerContract $container)
    {
        $container->toModel()->save();
    }

    public function delete($container)
    {
        $container->delete();
    }

    public static function bindings(): array
    {
        return [
            AssetContainerContract::class => AssetContainer::class,
        ];
    }
}
