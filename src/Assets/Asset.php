<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Statamic\Assets\Asset as FileAsset;
use Statamic\Assets\AssetUploader as Uploader;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Facades\Blink;
use Statamic\Facades\Path;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class Asset extends FileAsset
{
    protected $existsOnDisk = false;
    protected $removedData = [];

    public static function fromModel(Model $model)
    {
        return (new static())
            ->container($model->container)
            ->path(Str::replace('//', '/', $model->folder.'/'.$model->basename))
            ->hydrateMeta($model->meta);
    }

    public function meta($key = null)
    {
        if (func_num_args() === 1) {
            return $this->metaValue($key);
        }

        if (! config('statamic.assets.cache_meta')) {
            return $this->generateMeta();
        }

        if ($this->meta) {
            $meta = $this->meta;

            $meta['data'] = collect(Arr::get($meta, 'data', []))
                ->merge($this->data->all())
                ->except($this->removedData)
                ->all();

            return $meta;
        }

        return $this->meta = Cache::rememberForever($this->metaCacheKey(), function () {
            $handle = $this->container()->handle().'::'.$this->metaPath();
            if ($model = app('statamic.eloquent.assets.model')::where([
                'container' => $this->containerHandle(),
                'folder' => $this->folder(),
                'basename' => $this->basename(),
            ])->first()) {
                return $model->meta;
            }

            $this->writeMeta($meta = $this->generateMeta());

            if (! $meta['data']) {
                $meta['data'] = [];
            }

            return $meta;
        });
    }

    public function exists()
    {
        return $this->existsOnDisk || $this->metaExists();
    }

    public function metaExists()
    {
        return Blink::once('eloquent-asset-meta-exists-'.$this->id(), function () {
            return app('statamic.eloquent.assets.model')::query()
            ->where([
                'container' => $this->containerHandle(),
                'folder' => $this->folder(),
                'basename' => $this->basename(),
            ])->count() > 0;
        });
    }

    private function metaValue($key)
    {
        $value = Arr::get($this->meta(), $key);

        if (! is_null($value)) {
            return $value;
        }

        return Arr::get($this->generateMeta(), $key);
    }

    public function generateMeta()
    {
        if (! $this->disk()->exists($this->path())) {
            return ['data' => $this->data->all()];
        }

        $this->existsOnDisk = true;

        return parent::generateMeta();
    }

    public function hydrateMeta($meta)
    {
        $this->meta = $meta;

        return $this;
    }

    public function writeMeta($meta)
    {
        $meta['data'] = Arr::removeNullValues($meta['data']);

        self::makeModelFromContract($this, $meta);

        Blink::put('eloquent-asset-meta-exists-'.$this->id(), true);
    }

    public static function makeModelFromContract(AssetContract $source, $meta = [])
    {
        if (! $meta) {
            $meta = $source->meta();
        }

        $model = app('statamic.eloquent.assets.model')::firstOrNew([
            'container' => $source->containerHandle(),
            'folder' => $source->folder(),
            'basename' => $source->basename(),
        ])->fill([
            'meta' => $meta,
            'filename' => $source->filename(),
            'extension' => $source->extension(),
            'path' => $source->path(),
        ]);

        // Set initial timestamps.
        if (empty($model->created_at) && isset($meta['last_modified'])) {
            $model->created_at = $meta['last_modified'];
            $model->updated_at = $meta['last_modified'];
        }

        $model->save();

        return $model;
    }

    public function metaPath()
    {
        return $this->path();
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
        $filename = Uploader::getSafeFilename($filename ?: $this->filename());
        $oldPath = $this->path();
        $oldMetaPath = $this->metaPath();
        $oldFolder = $this->folder();
        $oldBasename = $this->basename();
        $newPath = Str::removeLeft(Path::tidy($folder.'/'.$filename.'.'.pathinfo($oldPath, PATHINFO_EXTENSION)), '/');

        $this->hydrate();
        $this->disk()->rename($oldPath, $newPath);
        $this->path($newPath);
        $this->save();

        if ($oldMetaPath != $this->metaPath()) {
            $oldMetaModel = app('statamic.eloquent.assets.model')::where([
                'container' => $this->containerHandle(),
                'folder' => $oldFolder,
                'basename' => $oldBasename,
            ])->first();

            if ($oldMetaModel) {
                $meta = $oldMetaModel->meta;
                $oldMetaModel->delete();

                $this->writeMeta($meta);
            }
        }

        return $this;
    }
}
