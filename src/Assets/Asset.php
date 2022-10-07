<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Support\Facades\Cache;
use Statamic\Assets\Asset as FileAsset;
use Statamic\Facades\Blink;
use Statamic\Support\Arr;

class Asset extends FileAsset
{
    public function meta($key = null)
    {
        if (func_num_args() === 1) {
            return $this->metaValue($key);
        }

        if (! config('statamic.assets.cache_meta')) {
            return $this->generateMeta();
        }

        if ($this->meta) {
            return array_merge($this->meta, ['data' => $this->data->all()]);
        }

        return $this->meta = Cache::rememberForever($this->metaCacheKey(), function () {
            $handle = $this->container()->handle().'::'.$this->metaPath();
            if ($model = app('statamic.eloquent.assets.model')::where('handle', $handle)->first()) {
                return $model->data;
            }

            $this->writeMeta($meta = $this->generateMeta());

            return $meta;
        });
    }

    public function exists()
    {
        $files = Blink::once($this->container()->handle().'::files', function () {
            return $this->container()->files();
        });

        if (! $path = $this->path()) {
            return false;
        }

        return $files->contains($path);
    }

    private function metaValue($key)
    {
        $value = Arr::get($this->meta(), $key);

        if (! is_null($value)) {
            return $value;
        }

        Cache::forget($this->metaCacheKey());

        $this->writeMeta($meta = $this->generateMeta());

        return Arr::get($meta, $key);
    }

    public function writeMeta($meta)
    {
        $meta['data'] = Arr::removeNullValues($meta['data']);

        $model = app('statamic.eloquent.assets.model')::firstOrNew([
            'handle' => $this->container()->handle().'::'.$this->metaPath(),
        ])->fill(['data' => $meta]);

        // Set initial timestamps.
        if (empty($model->created_at) && isset($meta['last_modified'])) {
            $model->created_at = $meta['last_modified'];
            $model->updated_at = $meta['last_modified'];
        }

        $model->save();
    }

    /**
     * Move the asset to a different location.
     *
     * @param  string  $folder  The folder relative to the container.
     * @param  string|null  $filename  The new filename, if renaming.
     * @return $this
     */
    public function move($folder, $filename = null)
    {
        $oldMeta = $this->metaPath();

        parent::move($folder, $filename);

        if ($oldMeta != $this->metaPath()) {
            if ($oldMeta = app('statamic.eloquent.assets.model')::whereHandle($this->container()->handle().'::'.$oldMeta)->first()) {
                $oldMeta->delete();
            }

            $this->writeMeta($this->meta());
        }

        return $this;
    }
}
