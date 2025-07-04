<?php

namespace Statamic\Eloquent\Assets;

use Statamic\Assets\AssetRepository as BaseRepository;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Contracts\Assets\QueryBuilder as QueryBuilderContract;
use Statamic\Facades\Blink;
use Statamic\Facades\Site;
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

        if (Str::startsWith($containerUrl, '/')) {
            $containerUrl = $siteUrl.$containerUrl;
        }

        if (Str::startsWith($containerUrl, $siteUrl)) {
            $url = $siteUrl.$url;
        }

        $path = Str::after($url, $containerUrl);

        if (Str::startsWith($path, '/')) {
            $path = substr($path, 1);
        }

        return $this->findById("{$container}::{$path}");
    }

    public function delete($asset)
    {
        if ($id = $asset->id()) {
            Blink::forget("eloquent-asset-{$id}");
        }

        $this->query()
            ->where([
                'container' => $asset->container(),
                'folder' => $asset->folder(),
                'basename' => $asset->basename(),
            ])
            ->delete();
    }

    public function save($asset)
    {
        $asset->writeMeta($asset->generateMeta());

        $id = $asset->id();
        Blink::put("eloquent-asset-{$id}", $asset);
    }

    public static function bindings(): array
    {
        return [
            AssetContract::class        => app('statamic.eloquent.assets.asset'),
            QueryBuilderContract::class => AssetQueryBuilder::class,
        ];
    }
}
