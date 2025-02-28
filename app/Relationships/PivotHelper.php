<?php

namespace App\Relationships;

use Illuminate\Database\Eloquent\Model;

class PivotHelper
{
    protected $model;

    protected $relationshipName;

    public function __construct(Model $model, string $relationshipName)
    {
        $this->model = $model;
        $this->relationshipName = $relationshipName;
    }

    public function relationship()
    {
        return $this->model->{$this->relationshipName}();
    }

    public function addPivots(array $items = [], array $attributes = [], $touch = true)
    {
        $pivots = [];
        foreach ($items as $item) {
            $pivots[] = $this->addPivot($item, $attributes, $touch);
        }

        return $pivots;
    }

    public function updatePivots(array $items = [], array $attributes = [], $touch = true)
    {
        $pivots = [];
        foreach ($items as $item) {
            $pivots[] = $this->updatePivot($item, $attributes, $touch);
        }

        return $pivots;
    }

    public function removePivots(array $items = [], $touch = true)
    {
        $pivots = [];
        foreach ($items as $item) {
            $pivots[] = $this->removePivot($item, $touch);
        }

        return $pivots;
    }

    public function addPivot(Model $item, array $attributes = [], $touch = true)
    {
        return $this->relationship()->attach($item, $attributes, $touch);
    }

    public function updatePivot(Model $item, array $attributes = [], $touch = true)
    {
        return $this->relationship()->updateExistingPivot($item, $attributes, $touch);
    }

    public function removePivot(Model $item, $touch = true)
    {
        return $this->relationship()->detach($item, $touch);
    }

    public function syncPivots($items, $detaching = true)
    {
        return $this->relationship()->sync($items, $detaching);
    }
}
