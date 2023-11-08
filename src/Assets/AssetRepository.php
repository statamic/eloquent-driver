<?php

namespace Statamic\Eloquent\Assets;

use Statamic\Assets\AssetRepository as BaseRepository;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Contracts\Assets\QueryBuilder as QueryBuilderContract;
use Statamic\Facades\Blink;
use Statamic\Facades\Stache;
use Statamic\Support\Str;

class AssetRepository extends BaseRepository
{
    public function findById($id): ?AssetContract
    {
        [$container, $path] = explode('::', $id);

        $filename = Str::afterLast($path, '/');
        $folder = str_contains($path, '/') ? Str::beforeLast($path, '/') : '/';

        $blinkKey = "eloquent-asset-{$id}";
        $item = Blink::once($blinkKey, function () use ($container, $filename, $folder) {
            return $this->query()
                ->where('container', $container)
                ->where('folder', $folder)
                ->where('basename', $filename)
                ->first();
        });

        if (! $item) {
            Blink::forget($blinkKey);

            return null;
        }

        return $item;
    }

    public function findByUrl(string $url)
    {
        if (! $container = $this->resolveContainerFromUrl($url)) {
            return null;
        }

        $siteUrl = rtrim(Site::current()->absoluteUrl(), '/');
        $containerUrl = $container->url();

        if (starts_with($containerUrl, '/')) {
            $containerUrl = $siteUrl.$containerUrl;
        }

        if (starts_with($containerUrl, $siteUrl)) {
            $url = $siteUrl.$url;
        }

        $path = str_after($url, $containerUrl);

        return $this->findById("{$container}::{$path}");
    }

    public function delete($asset)
    {
        $asset->container()->contents()->forget($asset->path())->save();

        $model = $this->query()
            ->where([
                'container' => $asset->container(),
                'folder' => $asset->folder(),
                'basename' => $asset->basename(),
            ])
            ->first();

        if ($model) {
            $model->delete();
        }
    }

    public static function bindings(): array
    {
        return [
            AssetContract::class        => app('statamic.eloquent.assets.asset'),
            QueryBuilderContract::class => AssetQueryBuilder::class,
        ];
    }
}
