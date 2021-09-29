<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Support\Collection;
use Statamic\Stache\Repositories\AssetContainerRepository as StacheRepository;
use Statamic\Contracts\Assets\AssetContainer as AssetContainerContract;
use Statamic\Facades\Blink;

class AssetContainerRepository extends StacheRepository
{
    public static function bindings(): array
    {
        return [
            AssetContainerContract::class => AssetContainer::class,
        ];
    }

    public function all(): Collection
    {
        return Blink::once('asset-containers', function () {
            $keys = AssetContainerModel::get()->map(function ($model) {
                return AssetContainer::fromModel($model);
            });

            return Collection::make($keys);
        });
    }

    public function find($handle): ?AssetContainer
    {
        return Blink::once('asset-containers::' . $handle, function () use ($handle) {
            if (($model = AssetContainerModel::where('handle', $handle)->first()) == null) {
                return null;
            }

            $assetContainer = AssetContainer::fromModel($model);

            return $assetContainer;
        });
    }

    public function findByHandle($handle): ?AssetContainer
    {
        return $this->find($handle);
    }

    public function save($assetContainer)
    {
        $model = $assetContainer->toModel();

        $model->save();

        $assetContainer->model($model->fresh());
    }

    public function delete($assetContainer)
    {
        $assetContainer->toModel()->delete();
    }
}
