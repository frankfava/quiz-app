<?php

namespace App\Filament\SuperUser\Resources\QuestionResource\Pages;

use App\Filament\SuperUser\Resources\QuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;
}
