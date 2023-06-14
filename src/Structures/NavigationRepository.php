<?php

namespace Statamic\Eloquent\Structures;

use Illuminate\Support\Collection;
use Statamic\Contracts\Structures\Nav as NavContract;
use Statamic\Facades\Blink;
use Statamic\Stache\Repositories\NavigationRepository as StacheRepository;

class NavigationRepository extends StacheRepository
{
    protected function transform($items, $columns = [])
    {
        return Collection::make($items)->map(function ($model) {
            return Nav::fromModel($model);
        });
    }

    public static function bindings(): array
    {
        return [
            NavContract::class => Nav::class,
        ];
    }

    public function all(): Collection
    {
        return $this->transform(app('statamic.eloquent.navigations.model')::all());
    }

    public function findByHandle($handle): ?NavContract
    {
        return Blink::once("eloquent-nav-{$handle}", function () use ($handle) {
            $model = app('statamic.eloquent.navigations.model')::whereHandle($handle)->first();

            return $model ? app(NavContract::class)->fromModel($model) : null;
        });
    }

    public function save($entry)
    {
        $model = $entry->toModel();
        $model->save();

        Blink::forget("eloquent-nav-{$model->handle}");

        $entry->model($model->fresh());
    }

    public function delete($entry)
    {
        $entry->model()->delete();
    }
}
