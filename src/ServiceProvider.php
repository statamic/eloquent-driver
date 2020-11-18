<?php

namespace Statamic\Eloquent;

use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Eloquent\Entries\CollectionRepository;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Eloquent\Entries\EntryQueryBuilder;
use Statamic\Eloquent\Entries\EntryRepository;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $config = false;

    public function boot()
    {
        parent::boot();

        $this->mergeConfigFrom($config = __DIR__.'/../config/eloquent-driver.php', 'statamic-eloquent-driver');

        if ($this->app->runningInConsole()) {
            $this->publishes([$config => config_path('statamic-eloquent-driver.php')]);
        }
    }

    public function register()
    {
        $this->registerEntries();
    }

    protected function registerEntries()
    {
        Statamic::repository(EntryRepositoryContract::class, EntryRepository::class);
        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);

        $this->app->bind(EntryQueryBuilder::class, function () {
            return new EntryQueryBuilder(EntryModel::query());
        });

        $this->app->bind('statamic.eloquent.entries.model', function () {
            return config('statamic-eloquent-driver.entries.model');
        });
    }
}
