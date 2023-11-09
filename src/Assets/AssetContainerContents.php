<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use League\Flysystem\DirectoryListing;
use Statamic\Assets\AssetContainerContents as CoreAssetContainerContents;
use Statamic\Statamic;
use Statamic\Support\Str;

class AssetContainerContents extends CoreAssetContainerContents
{
    protected $container;
    protected $folders;

    private function query()
    {
        return app('statamic.eloquent.assets.model')::query()
            ->where('container', $this->container->handle());
    }

    public function all()
    {
        // for performance reasons we only return directories to
        // avoid returning thousands of models
        return $this->directories()->keyBy('path');
    }

    public function files()
    {
        return $this->query()
            ->select(['path'])
            ->get()
            ->keyBy('path');
    }

    public function directories()
    {
        if ($this->folders && ! Statamic::isWorker()) {
            return $this->folders;
        }

        $this->folders = Cache::remember($this->key(), $this->ttl(), function () {
            return $this->container->disk()->getFolders('/', true)
                ->map(fn ($dir) => ['path' => $dir]);
        });

        return $this->folders;
    }

    public function metaFilesIn($folder, $recursive)
    {
        return $this->query()
            ->where(fn ($query) => $query
                ->where('folder', $folder)
                ->when($recursive, fn ($query) => $query->orWhere('folder', 'like', ($folder != '/' ? $folder : '').'/%'))
            )
            ->get()
            ->keyBy('path');
    }

    public function filteredFilesIn($folder, $recursive)
    {
        return $this->metaFilesIn($folder, $recursive);
    }

    public function filteredDirectoriesIn($folder, $recursive)
    {
        $folder = $folder == '/' ? '' : $folder;

        return $this->directories()->filter(function ($dir) use ($folder, $recursive) {
            if ($folder && ! Str::startsWith($dir, $folder)) {
                return false;
            }

            return ! $recursive ? Str::of($dir)->after($folder.'/')->contains('/') == false : true;
        })->flip();
    }

    public function save()
    {
        Cache::put($this->key(), $this->folders, $this->ttl());
    }

    public function forget($path)
    {
        $this->directories();

        $this->folders = $this->folders->reject(fn ($dir) => $dir['path'] == $path);

        return $this;
    }

    public function add($path)
    {
        $this->directories();

        // Add parent directories
        if (($dir = dirname($path)) !== '.') {
            $this->add($dir);
        }

        $this->folders->push(['path' => $path]);

        return $this;
    }

    private function key()
    {
        return 'asset-folder-contents-'.$this->container->handle();
    }

    private function ttl()
    {
        return config('statamic.stache.watcher') ? 0 : null;
    }
}
