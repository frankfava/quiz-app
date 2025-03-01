<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';

    public static function getLabels(): array
    {
        return [
            self::ADMIN->value => 'Admin',
        ];
    }

    public function getLabel(): string
    {
        return self::getLabels()[$this->value];
    }
}
