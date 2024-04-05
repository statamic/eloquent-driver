<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Database\Eloquent\Model;
use Statamic\Assets\AssetContainer as FileEntry;
use Statamic\Events\AssetContainerDeleted;
use Statamic\Events\AssetContainerSaved;
use Statamic\Support\Str;

class AssetContainer extends FileEntry
{
    protected $title;

    protected $handle;

    protected $disk;

    protected $private;

    protected $allowUploads;

    protected $allowDownloading;

    protected $allowMoving;

    protected $allowRenaming;

    protected $createFolders;

    protected $searchIndex;

    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static())->fillFromModel($model);
    }

    public function fillFromModel(Model $model)
    {
        $this
            ->title($model->title)
            ->handle($model->handle)
            ->disk($model->disk ?? config('filesystems.default'))
            ->allowUploads($model->settings['allow_uploads'] ?? null)
            ->allowDownloading($model->settings['allow_downloading'] ?? null)
            ->allowMoving($model->settings['allow_moving'] ?? null)
            ->allowRenaming($model->settings['allow_renaming'] ?? null)
            ->createFolders($model->settings['create_folders'] ?? null)
            ->searchIndex($model->settings['search_index'] ?? null)
            ->sourcePreset($model->settings['source_preset'] ?? null)
            ->warmPresets($model->settings['warm_presets'] ?? null)
            ->model($model);

        return $this;
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.assets.container_model');

        return $class::firstOrNew(['handle' => $this->handle()])->fill([
            'title'    => $this->title(),
            'disk'     => $this->diskHandle() ?? config('filesystems.default'),
            'settings' => [
                'allow_uploads'     => $this->allowUploads(),
                'allow_downloading' => $this->allowDownloading(),
                'allow_moving'      => $this->allowMoving(),
                'allow_renaming'    => $this->allowRenaming(),
                'create_folders'    => $this->createFolders(),
                'search_index'      => $this->searchIndex(),
                'source_preset'     => $this->sourcePreset,
                'warm_presets'      => $this->warmPresets,
            ],
        ]);
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

    public function folders($folder = '/', $recursive = false)
    {
        return $this->disk()->getFolders($folder, $recursive);
    }

    public function metaFiles($folder = '/', $recursive = false)
    {
        // When requesting files() as-is, we want all of them.
        if (func_num_args() === 0) {
            $recursive = true;
        }

        return $this->queryAssets()
            ->when($recursive, fn ($query) => $query->where('folder', $folder), fn ($query) => $query->where('folder', 'like', Str::replaceEnd('/', '', $folder).'/%'))
            ->get()
            ->pluck('path');
    }
}
