<?php

namespace App\Models;

use Database\Factories\AssessmentRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentRecord extends Model
{
    /** @use HasFactory<AssessmentRecordFactory> */
    use HasFactory;

    protected $fillable = ['training_application_id', 'scheduled_at', 'status', 'result', 'notes', 'recorded_by'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(TrainingApplication::class, 'training_application_id');
    }
}
