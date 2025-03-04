<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';

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
