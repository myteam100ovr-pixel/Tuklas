<?php

namespace App\Models;

use Database\Factories\TrainingApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingApplication extends Model
{
    /** @use HasFactory<TrainingApplicationFactory> */
    use HasFactory;

    protected $fillable = ['training_program_id', 'training_session_id', 'user_id', 'status', 'decision_reason', 'is_demo'];

    protected static function booted(): void
    {
        static::saving(function (self $application): void {
            $status = (string) ($application->status ?? 'pending');
            $isActive = in_array($status, ['pending', 'accepted'], true);

            $application->setAttribute(
                'active_application_key',
                $isActive ? hash('sha256', $application->user_id.':'.$application->training_program_id) : null,
            );
        });
    }

    protected function casts(): array
    {
        return ['is_demo' => 'boolean'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
