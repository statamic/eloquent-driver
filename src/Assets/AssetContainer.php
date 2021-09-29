<?php

namespace Statamic\Eloquent\Assets;

use Statamic\Facades\AssetContainer as AssetContainerFacade;
use Statamic\Assets\AssetContainer as FileAssetContainer;

class AssetContainer extends FileAssetContainer
{
    protected $model;

    public static function fromModel(AssetContainerModel $model)
    {
        $data = $model->data;

        return AssetContainerFacade::make($model->handle)
            ->disk(array_get($data, 'disk'))
            ->title(array_get($data, 'title'))
            ->allowDownloading(array_get($data, 'allow_downloading'))
            ->allowMoving(array_get($data, 'allow_moving'))
            ->allowRenaming(array_get($data, 'allow_renaming'))
            ->allowUploads(array_get($data, 'allow_uploads'))
            ->createFolders(array_get($data, 'create_folders'))
            ->searchIndex(array_get($data, 'search_index'));
    }

    public function toModel()
    {
        $model = AssetContainerModel::firstOrNew([
            'handle' => $this->id(),
        ]);

        $model->data = $this->fileData();
        $model->save();

        return $model;
    }

    public function model($model = null)
    {
        if (func_num_args() === 0) {
            return $this->model;
        }

        $this->model = $model;

        $this->id($model->handle);

        return $this;
    }

    public function lastModified()
    {
        return $this->model->updated_at;
    }
}
