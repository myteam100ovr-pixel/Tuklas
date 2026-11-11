<?php

namespace App\Models;

use Database\Factories\TrainingCenterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingCenter extends Model
{
    /** @use HasFactory<TrainingCenterFactory> */
    use HasFactory;

    protected $fillable = ['name', 'type', 'address', 'contact_email', 'contact_phone', 'is_active', 'is_demo'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_demo' => 'boolean'];
    }

    public function representatives(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(TrainingProgram::class);
    }
}
