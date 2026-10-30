<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerChatMessage extends Model
{
    protected $fillable = ['role', 'text', 'sources'];

    protected function casts(): array
    {
        return ['sources' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
