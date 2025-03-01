<?php

namespace App\Filament\SuperUser\Resources\UserResource\Pages;

use App\Filament\SuperUser\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
