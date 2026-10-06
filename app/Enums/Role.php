<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Trainer = 'trainer';
    case Youth = 'youth';

    public function homeRoute(): string
    {
        return match ($this) {
            self::SuperAdmin => 'admin.dashboard',
            self::Trainer => 'trainer.dashboard',
            self::Youth => 'youth.dashboard',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'PESO Bugallon (Super Admin)',
            self::Trainer => 'TESDA Lingayen (Trainer)',
            self::Youth => 'Youth',
        };
    }
}