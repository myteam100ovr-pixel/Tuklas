<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Trainer = 'trainer';
    case TesdaAdmin = 'tesda_admin';
    case CenterRepresentative = 'center_representative';
    case Youth = 'youth';

    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Trainer => 'trainer.dashboard',
            self::TesdaAdmin, self::CenterRepresentative => 'trainer.dashboard',
            self::Youth => 'youth.dashboard',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'PESO Bugallon Admin',
            self::Trainer => 'Trainer',
            self::TesdaAdmin => 'TESDA',
            self::CenterRepresentative => 'Training Center Representative',
            self::Youth => 'Career Seeker',
        };
    }
}
