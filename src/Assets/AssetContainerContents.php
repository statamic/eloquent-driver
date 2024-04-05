<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Support\Facades\Cache;
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
            return collect($this->directoryRecurse(''))
                ->map(fn ($dir) => ['path' => $dir, 'type' => 'dir']);
        });

        return $this->folders;
    }

    private function directoryRecurse($directory)
    {
        $rootFolders = $this->container->disk()->getFolders($directory, false);

        $folders = [];
        foreach ($rootFolders as $folder) {
            $folders[] = $folder;
            if ($subfolders = $this->directoryRecurse($folder)) {
                $folders = array_merge($folders, $subfolders);
            }
        }

        return $folders;
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

        return $this->directories()
            ->filter(function ($dir) use ($folder) {
                if ($folder && ! Str::startsWith($dir['path'], $folder)) {
                    return false;
                }

                if ($folder == $dir['path']) {
                    return false;
                }

                return true;
            })
            ->map(function ($dir) {
                $dirs = [];
                $tmp = '';
                foreach (explode('/', $dir['path']) as $dir) {
                    $tmp .= '/'.$dir;
                    $dirs[] = substr($tmp, 1);
                }

                return $dirs;
            })
            ->flatten()
            ->unique()
            ->filter(function ($dir) use ($folder, $recursive) {
                if ($folder == $dir || strlen($folder) > strlen($dir)) {
                    return false;
                }

                if ($recursive) {
                    return true;
                }

                $dir = Str::of($dir);
                if ($folder) {
                    $dir = $dir->after($folder.'/');
                }

                return ! $dir->contains('/');
            })
            ->sort()
            ->flip();
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
