<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    // role and is_active are deliberately NOT fillable. Roles are assigned in code only.
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    protected static function booted(): void
    {
        static::updated(function (User $user): void {
            if ($user->wasChanged(['role', 'is_active'])) {
                $user->tokens()->delete();
            }
        });

        static::deleted(function (User $user): void {
            $user->tokens()->delete();
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'date_of_birth' => 'date',
            'admin_password_reset_requested_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function youthProfile(): HasOne
    {
        return $this->hasOne(YouthProfile::class);
    }

    public function documentScans(): HasMany
    {
        return $this->hasMany(DocumentScan::class);
    }

    public function careerChatMessages(): HasMany
    {
        return $this->hasMany(CareerChatMessage::class);
    }

    public function sentDirectMessages(): HasMany
    {
        return $this->hasMany(DirectMessage::class, 'sender_id');
    }

    public function receivedDirectMessages(): HasMany
    {
        return $this->hasMany(DirectMessage::class, 'recipient_id');
    }

    public function tesdaChoice(): HasOne
    {
        return $this->hasOne(TesdaChoice::class);
    }

    public function trainingApplications(): HasMany
    {
        return $this->hasMany(TrainingApplication::class);
    }

    public function scholarshipApplications(): HasMany
    {
        return $this->hasMany(ScholarshipApplication::class);
    }

    public function trainingCenter(): BelongsTo
    {
        return $this->belongsTo(TrainingCenter::class);
    }

    public function recommendedJobs(): BelongsToMany
    {
        return $this->belongsToMany(JobOpportunity::class, 'job_recommendations')->withPivot(['hired_at', 'hired_by'])->withTimestamps();
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class);
    }

    public function trainerQualifications(): HasMany
    {
        return $this->hasMany(TrainerQualification::class);
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isCenterScopedTesda(): bool
    {
        return $this->hasRole(Role::TesdaAdmin) && $this->training_center_id !== null;
    }

    protected function defaultProfilePhotoUrl(): string
    {
        $initials = collect(preg_split('/\s+/', trim($this->name)))
            ->filter()->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96"><rect width="96" height="96" fill="#5b17ff"/>'
            .'<text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" font-family="Arial,sans-serif" font-size="38" fill="#fff">'
            .e($initials).'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
