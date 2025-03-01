<?php

namespace App\Filament;

use Filament\Resources\Resource as BaseResource;
use Illuminate\Support\Str;

abstract class Resource extends BaseResource
{
    public static function getTitleCaseModelLabel(): string
    {
        if (! static::hasTitleCaseModelLabel()) {
            return static::getModelLabel();
        }

        return Str::title(static::getModelLabel());
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        if (! static::hasTitleCaseModelLabel()) {
            return static::getPluralModelLabel();
        }

        return Str::title(static::getPluralModelLabel());
    }
}
