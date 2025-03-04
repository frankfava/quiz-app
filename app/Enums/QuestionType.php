<?php

namespace App\Enums;

enum QuestionType: string
{
    case MULTIPLE_CHOICE = 'multiple_choice';
    case OPEN_TEXT = 'open_text';
    case BOOLEAN = 'boolean';
    case MULTIPLE_RESPONSE = 'multiple_response';
    case ORDERING = 'ordering';
    case MATCHING = 'matching';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getLabels(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($str) => str($str)->headline()->lower()->ucfirst()->toString(), self::values())
        );
    }

    public function getLabel(): string
    {
        return self::getLabels()[$this->value];
    }
}
