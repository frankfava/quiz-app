<?php

namespace App\Enums;

enum QuizType: string
{
    case STANDARD = 'standard';
    case FOLLOW_ALONG = 'follow_along';

    public static function default(): static
    {
        return self::FOLLOW_ALONG;
    }

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
