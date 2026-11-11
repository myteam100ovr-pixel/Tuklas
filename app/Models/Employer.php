<?php

namespace App\Models;

use Database\Factories\EmployerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employer extends Model
{
    /** @use HasFactory<EmployerFactory> */
    use HasFactory;

    protected $fillable = ['name', 'location', 'contact_email', 'website', 'is_verified', 'is_demo'];

    protected function casts(): array
    {
        return ['is_verified' => 'boolean', 'is_demo' => 'boolean'];
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(JobOpportunity::class);
    }
}
