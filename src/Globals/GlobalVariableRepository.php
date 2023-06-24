<?php

namespace Statamic\Eloquent\Globals;

use Statamic\Contracts\Globals\Variables;
use Statamic\Globals\VariableCollection;
use Statamic\Stache\Repositories\GlobalVariableRepository as StacheRepository;
use Statamic\Support\Str;

class GlobalVariableRepository extends StacheRepository
{
    public function all(): VariableCollection
    {
        return VariableCollection::make(
            VariablesModel::all()
            ->each(function ($model) {
                return app(Variables::class)::fromModel($model);
            })
        );
    }

    public function find($id): ?Variables
    {
        $id = Str::split($id, '::');

        $model = VariablesModel::query()
            ->where('handle', $id[0])
            ->when(count($id) > 1, function ($query) use ($id) {
                $query->where('locale', $id[1]);
            })
            ->first();

        if (! $model) {
            return;
        }

        return app(Variables::class)::fromModel($model);
    }

    public function findBySet($handle): ?VariableCollection
    {
        return VariableCollection::make(
            VariablesModel::query()
                ->where('handle', $handle)
                ->get()
                ->each(function ($model) {
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
