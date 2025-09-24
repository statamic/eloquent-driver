<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Database\Eloquent\Model;
use Statamic\Assets\AssetContainer as FileEntry;
use Statamic\Contracts\Assets\AssetContainer as AssetContainerContract;
use Statamic\Events\AssetContainerDeleted;
use Statamic\Events\AssetContainerSaved;
use Statamic\Support\Str;

class AssetContainer extends FileEntry
{
    protected $title;

    protected $handle;

    protected $disk;

    protected $private;

    protected $searchIndex;

    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static)->fillFromModel($model);
    }

    public function fillFromModel(Model $model)
    {
        $this
            ->title($model->title)
            ->handle($model->handle)
            ->disk($model->disk ?? config('filesystems.default'))
            ->searchIndex($model->settings['search_index'] ?? null)
            ->sourcePreset($model->settings['source_preset'] ?? null)
            ->warmPresets($model->settings['warm_presets'] ?? null)
            ->validationRules($model->settings['validation_rules'] ?? null)
            ->sortField($model->settings['sort_by'] ?? null)
            ->sortDirection($model->settings['sort_dir'] ?? null)
            ->model($model);

        return $this;
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(AssetContainerContract $source)
    {
        $model = app('statamic.eloquent.assets.container_model')::firstOrNew(['handle' => $source->handle()])->fill([
            'title'    => $source->title(),
            'disk'     => $source->diskHandle() ?? config('filesystems.default'),
            'settings' => [],
        ]);

        $model->settings = array_merge($model->settings ?? [], [
            'search_index'      => $source->searchIndex(),
            'source_preset'     => $source->sourcePreset,
            'warm_presets'      => $source->warmPresets,
            'validation_rules'  => $source->validationRules(),
            'sort_by'           => $source->sortField(),
            'sort_dir'          => $source->sortDirection(),
        ]);

        // Set initial timestamps.
        if (empty($model->created_at) && isset($meta['last_modified'])) {
            $model->created_at = $source->fileLastModified();
            $model->updated_at = $source->fileLastModified();
        }

        return $model;
    }

    public function model($model = null)
    {
        if (func_num_args() === 0) {
            return $this->model;
        }

        $this->model = $model;

        return $this;
    }

    public function save()
    {
        $model = $this->toModel();
        $model->save();

        $this->fillFromModel($model->fresh());

        AssetContainerSaved::dispatch($this);

        return $this;
    }

    public function delete()
    {
        $this->model()->delete();

        AssetContainerDeleted::dispatch($this);

        return true;
    }

    public function metaFiles($folder = '/', $recursive = false)
    {
        // When requesting files() as-is, we want all of them.
        if (func_num_args() === 0) {
            $recursive = true;
        }

        return $this->queryAssets()
            ->when($recursive, fn ($query) => $query->where('folder', $folder), fn ($query) => $query->where('folder', 'like', Str::replaceEnd('/', '', $folder).'/%'))
            ->lazy()
            ->map(fn ($value) => $value->path());
    }
}
