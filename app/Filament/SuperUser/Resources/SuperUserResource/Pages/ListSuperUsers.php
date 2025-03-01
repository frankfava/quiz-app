<?php

namespace App\Filament\SuperUser\Resources\SuperUserResource\Pages;

use App\Filament\SuperUser\Resources\SuperUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSuperUsers extends ListRecords
{
    protected static string $resource = SuperUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
