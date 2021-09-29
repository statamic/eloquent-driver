<?php

namespace Statamic\Eloquent\Assets;

use Statamic\Assets\AssetRepository as BaseRepository;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Contracts\Assets\QueryBuilder;
use Statamic\Facades\Stache;

class AssetRepository extends BaseRepository
{
    public function delete($asset)
    {
        $asset->container()->contents()->forget($asset->path())->save();

        AssetModel::where('handle', $asset->containerHandle() . '::' . $asset->metaPath())->first()->delete();

        Stache::store('assets::' . $asset->containerHandle())->delete($asset);
    }

    public static function bindings(): array
    {
        return [
            AssetContract::class => Asset::class,
            QueryBuilder::class => \Statamic\Assets\QueryBuilder::class,
        ];
    }
}
