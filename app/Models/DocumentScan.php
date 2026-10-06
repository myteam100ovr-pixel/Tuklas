<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'doc_type', 'original_name', 'mime', 'size', 'stored_path',
        'status', 'progress', 'result', 'error', 'processed_at',
    ];

    protected function casts(): array
    {
        return ['result' => 'array', 'processed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
