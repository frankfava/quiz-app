<?php

namespace App\Enums;

enum QuestionDifficulty: string
{
    case EASY = 'easy';
    case MEDIUM = 'medium';
    case HARD = 'hard';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
