<?php

namespace App\Filament\SuperUser\Resources\SuperUserResource\Pages;

use App\Filament\SuperUser\Resources\SuperUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSuperUser extends EditRecord
{
    protected static string $resource = SuperUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
