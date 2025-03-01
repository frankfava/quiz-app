<?php

namespace App\Filament\SuperUser\Resources\TenantResource\Pages;

use App\Filament\SuperUser\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
}
