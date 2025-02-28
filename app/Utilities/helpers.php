<?php

use Illuminate\Support\Str;

/***************
** Models
***************/

/**
 * guessModelName
 */
function guessModelName(string $modelName)
{
    $modelName = Str::studly(Str::singular($modelName));
    if (class_exists($modelName)) {
        return $modelName;
    }

    return "App\\Models\\$modelName";
}

/**
 * guessRelationshipName
 */
function guessRelationshipName($model, string $related)
{
    $guess = Str::camel(Str::plural(class_basename($related)));

    return method_exists($model, $guess) ? $guess : Str::singular($guess);
}

/**
 * guessModel
 */
function guessModel(string $modelName)
{
    try {
        return app(guessModelName($modelName));
    } catch (Exception $e) {
        return false;
    }
}
