<?php

namespace App\Filament\SuperUser\Resources\QuizResource\Pages;

use App\Filament\SuperUser\Resources\QuizResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateQuiz extends CreateRecord
{
    protected static string $resource = QuizResource::class;
}
