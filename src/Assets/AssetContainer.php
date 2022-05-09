<?php

namespace Statamic\Eloquent\Assets;

use Statamic\Assets\AssetContainer as FileEntry;
use Statamic\Contracts\Assets\AssetContainer as AssetContainerContract;
use Statamic\Eloquent\Assets\AssetContainerModel as Model;
use Statamic\Events\AssetContainerDeleted;
use Statamic\Events\AssetContainerSaved;

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
        return (new static)
            ->title($model->title)
            ->handle($model->handle)
            ->disk($model->disk ?? null)
            ->allowUploads($model->settings['allow_uploads'] ?? null)
            ->allowDownloading($model->settings['allow_downloading'] ?? null)
            ->allowMoving($model->settings['allow_moving'] ?? null)
            ->allowRenaming($model->settings['allow_renaming'] ?? null)
            ->createFolders($model->settings['create_folders'] ?? null)
            ->searchIndex($model->settings['search_index'] ?? null)
            ->model($model);
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.assets.container_model');

        return $class::findOrNew($this->model?->id)->fill([
            'title' => $this->title(),
            'handle' => $this->handle(),
            'disk' => $this->diskHandle() ?? '',
            'settings' => [
                'allow_uploads' => $this->allowUploads(),
                'allow_downloading' => $this->allowDownloading(),
                'allow_moving' => $this->allowMoving(),
                'allow_renaming' => $this->allowRenaming(),
                'create_folders' => $this->createFolders(),
                'search_index' => $this->searchIndex(),
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

        $this->model($model->fresh());

        AssetContainerSaved::dispatch($this);

        return $this;
    }

    public function delete()
    {
        $this->model()->delete();

        AssetContainerDeleted::dispatch($this);

        return true;
    }
}
