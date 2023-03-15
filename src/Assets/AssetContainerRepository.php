<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Support\Collection;
use Statamic\Contracts\Assets\AssetContainer as AssetContainerContract;
use Statamic\Facades\Blink;
use Statamic\Stache\Repositories\AssetContainerRepository as StacheRepository;

class AssetContainerRepository extends StacheRepository
{
    protected $store;

    public function all(): Collection
    {
        return Blink::once('eloquent-assetcontainers', function () {
            return app('statamic.eloquent.assets.container_model')::all()
                ->map(function ($model) {
                    return Blink::once("eloquent-assetcontainers-{$model->handle}", function () use ($model) {
                        return app(AssetContainerContract::class)->fromModel($model);
                    });
                });
        });
    }

    public function findByHandle(string $handle): ?AssetContainerContract
    {
        return Blink::once("eloquent-assetcontainers-{$handle}", function () use ($handle) {
            $model = app('statamic.eloquent.assets.container_model')::whereHandle($handle)->first();

            if (! $model) {
                return null;
            }

            return app(AssetContainerContract::class)->fromModel($model);
        });
    }

    public function make(string $handle = null): AssetContainerContract
    {
        return app(AssetContainerContract::class)->handle($handle);
    }

    public function save(AssetContainerContract $container)
    {
        $container->save();

        Blink::forget("eloquent-assetcontainers-{$container->handle()}");
    }

    public function delete($container)
    {
        $container->delete();

        Blink::forget("eloquent-assetcontainers-{$container->handle()}");
        Blink::forget('eloquent-assetcontainers');
    }

    public static function bindings(): array
    {
        return [
            AssetContainerContract::class => AssetContainer::class,
        ];
    }
}
