<?php

namespace Statamic\Eloquent\Globals;

use Statamic\Contracts\Globals\Variables;
use Statamic\Globals\VariablesCollection;
use Statamic\Stache\Repositories\GlobalVariablesRepository as StacheRepository;
use Statamic\Support\Str;

class GlobalVariablesRepository extends StacheRepository
{
    public function all(): VariablesCollection
    {
        return VariablesCollection::make(
            app('statamic.eloquent.global_set_variables.model')::all()
                ->each(function ($model) {
                    return app(Variables::class)::fromModel($model);
                })
        );
    }

    public function find($id): ?Variables
    {
        $id = Str::split($id, '::');

        $model = app('statamic.eloquent.global_set_variables.model')::query()
            ->where('handle', $id[0])
            ->when(count($id) > 1, function ($query) use ($id) {
                $query->where('locale', $id[1]);
            })
            ->first();

        if (! $model) {
            return null;
        }

        return app(Variables::class)::fromModel($model);
    }

    public function whereSet($handle): VariablesCollection
    {
        return VariablesCollection::make(
            app('statamic.eloquent.global_set_variables.model')::query()
                ->where('handle', $handle)
                ->get()
                ->map(function ($model) {
                    return app(Variables::class)::fromModel($model);
                })
        );
    }

    public function save($variable)
    {
        $model = $variable->toModel();
        $model->save();

        $variable->model($model->fresh());
    }

    public function delete($variable)
    {
        $variable->model()->delete();
    }

    public static function bindings(): array
    {
        return [
            Variables::class => \Statamic\Eloquent\Globals\Variables::class,
        ];
    }
}
