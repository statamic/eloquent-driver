<?php

namespace Statamic\Eloquent\Fields;

trait PreservesSetOrder
{
    private function addOrderToSets($fields)
    {
        return collect($fields)
            ->map(function ($field) {
                if (isset($field['field']['sets']) && is_array($field['field']['sets'])) {
                    $field['field']['sets'] = $this->addOrderToSetGroups($field['field']['sets']);
                }

                return $field;
            })
            ->toArray();
    }

    private function addOrderToSetGroups($sets)
    {
        $count = 0;

        return collect($sets)
            ->map(function ($set) use (&$count) {
                $set['__count'] = $count++;

                if (isset($set['sets']) && is_array($set['sets'])) {
                    $set['sets'] = $this->addOrderToSetGroups($set['sets']);
                }

                if (isset($set['fields']) && is_array($set['fields'])) {
                    $set['fields'] = $this->addOrderToSets($set['fields']);
                }

                return $set;
            })
            ->toArray();
    }

    private function updateOrderFromSets($fields)
    {
        return collect($fields)
            ->map(function ($field) {
                if (isset($field['field']['sets']) && is_array($field['field']['sets'])) {
                    $field['field']['sets'] = $this->updateOrderFromSetGroups($field['field']['sets']);
                }

                return $field;
            })
            ->toArray();
    }

    private function updateOrderFromSetGroups($sets)
    {
        return collect($sets)
            ->sortBy('__count')
            ->map(function ($set) {
                unset($set['__count']);

                if (isset($set['sets']) && is_array($set['sets'])) {
                    $set['sets'] = $this->updateOrderFromSetGroups($set['sets']);
                }

                if (isset($set['fields']) && is_array($set['fields'])) {
                    $set['fields'] = $this->updateOrderFromSets($set['fields']);
                }

                return $set;
            })
            ->toArray();
    }
}
