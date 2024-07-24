<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Statamic\Assets\Asset as FileAsset;
use Statamic\Assets\AssetUploader as Uploader;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Data\HasDirtyState;
use Statamic\Facades\Blink;
use Statamic\Facades\Path;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class Asset extends FileAsset
{
    use HasDirtyState {
        syncOriginal as traitSyncOriginal;
    }

    public function syncOriginal()
    {
        // FileAsset overrides the trait method in order to add the "pending
        // data" logic. We don't need it here since everything comes from
        // the model so we'll just use the original trait method again.
        return $this->traitSyncOriginal();
    }

    protected $existsOnDisk = false;
    protected $removedData = [];

    public static function fromModel(Model $model)
    {
        return (new static)
            ->container($model->container)
            ->path(Str::replace('//', '/', $model->folder.'/'.$model->basename))
            ->hydrateMeta($model->meta)
            ->syncOriginal();
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

        return Blink::once($this->metaCacheKey(), function () {
            if ($model = app('statamic.eloquent.assets.model')::where([
                'container' => $this->containerHandle(),
                'folder' => $this->folder(),
                'basename' => $this->basename(),
            ])->first()) {
                return $model->meta;
            }

            $meta = $this->generateMeta();

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

        self::makeModelFromContract($this, $meta)?->save();

        Blink::put('eloquent-asset-meta-exists-'.$this->id(), true);
    }

    public static function makeModelFromContract(AssetContract $source, $meta = [])
    {
        if (! $meta) {
            $meta = $source->meta();
        }

        // Make shure that a extension could be found, as the extension field is required.
        if (! $extension = $source->extension()) {
            return null;
        }

        $original = $source->getOriginal();

        $model = app('statamic.eloquent.assets.model')::firstOrNew([
            'container' => $source->containerHandle(),
            'folder' => Arr::get($original, 'folder', $source->folder()),
            'basename' => Arr::get($original, 'basename', $source->basename()),
        ])->fill([
            'meta' => $meta,
            'filename' => $source->filename(),
            'extension' => $extension,
            'path' => $source->path(),
            'folder' => $source->folder(),
            'basename' => $source->basename(),
        ]);

        // Set initial timestamps.
        if (empty($model->created_at) && isset($meta['last_modified'])) {
            $model->created_at = $meta['last_modified'];
            $model->updated_at = $meta['last_modified'];
        }

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

    public function getCurrentDirtyStateAttributes(): array
    {
        return array_merge([
            'path' => $this->path(),
            'folder' => $this->folder(),
            'basename' => $this->basename(),
            'data' => $this->data()->toArray(),
        ]);
    }

    protected function clearCaches()
    {
        $this->meta = null;

        Blink::forget("eloquent-asset-{$this->id()}");
        Blink::forget($this->metaCacheKey());
        Cache::forget($this->metaCacheKey());
    }
}
