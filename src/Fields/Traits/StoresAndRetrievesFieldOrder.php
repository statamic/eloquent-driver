<?php

namespace Statamic\Eloquent\Fields\Traits;

use Statamic\Support\Arr;

trait StoresAndRetrievesFieldOrder
{
    private function addOrderToBlueprintFields(array $fields): array
    {
        return collect($fields)->map(function ($field) {
            if (in_array($field['field']['type'], ['replicator'])) {
                $field['field']['sets'] = collect($field['field']['sets'])->map(function ($set) {
                    $set['sets'] = collect($set['sets'])->map(function ($set) {
                        $set['fields'] = $this->addOrderToBlueprintFields($set['fields']);

                        return $set;
                    })->all();

                    return $set;
                })->all();

                return $field;
            }

            if (in_array($field['field']['type'], ['grid'])) {
                $field['field']['fields'] = $this->addOrderToBlueprintFields($field['field']['fields']);

                return $field;
            }

            if (!in_array($field['field']['type'], ['select'])) {
                return $field;
            }

            $field['field']['__order'] = array_keys($field['field']['options']);

            return $field;
        })->all();
    }

    private function applyOrderToBlueprintFields(array $fields): array
    {
        return collect($fields)->map(function ($field) {
            if (in_array($field['field']['type'], ['replicator'])) {
                $field['field']['sets'] = collect($field['field']['sets'])->map(function ($set) {
                    $set['sets'] = collect($set['sets'])->map(function ($set) {
                        $set['fields'] = $this->applyOrderToBlueprintFields($set['fields']);

                        return $set;
                    })->all();

                    return $set;
                })->all();

                return $field;
            }

            if (in_array($field['field']['type'], ['grid'])) {
                $field['field']['fields'] = $this->applyOrderToBlueprintFields($field['field']['fields']);

                return $field;
            }

            if (! $orderedKeys = Arr::get($field, 'field.__order')) {
                return $field;
            }

            $field['field']['options'] = array_merge(array_flip($orderedKeys), $field['field']['options']);
            unset($field['field']['__order']);

            return $field;
        })->all();
    }
}
