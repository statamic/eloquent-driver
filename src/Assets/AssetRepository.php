<?php

namespace Statamic\Eloquent\Assets;

use Statamic\Assets\AssetRepository as BaseRepository;
use Statamic\Assets\QueryBuilder;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Contracts\Assets\QueryBuilder as QueryBuilderContract;
use Statamic\Facades\Stache;

class AssetRepository extends BaseRepository
{
    public function delete($asset)
    {
        $asset->container()->contents()->forget($asset->path())->save();

        $handle = $asset->containerHandle().'::'.$asset->metaPath();
        app('statamic.eloquent.assets.model')::where('handle', $handle)->first()->delete();

        Stache::store('assets::'.$asset->containerHandle())->delete($asset);
    }

    public static function bindings(): array
    {
        return [
            AssetContract::class        => Asset::class,
            QueryBuilderContract::class => QueryBuilder::class,
        ];
    }
}
