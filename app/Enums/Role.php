<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case TesdaAdmin = 'tesda_admin';
    case Youth = 'youth';

    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::TesdaAdmin => 'trainer.dashboard',
            self::Youth => 'youth.dashboard',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'PESO Bugallon Admin',
            self::TesdaAdmin => 'TESDA',
            self::Youth => 'Career Seeker',
        };
    }
}
