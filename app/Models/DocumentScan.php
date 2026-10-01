<?php

namespace App\Models;

use Database\Factories\DocumentScanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentScan extends Model
{
    /** @use HasFactory<DocumentScanFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_type',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'status',
        'analysis',
        'failure_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'analysis' => 'array',
            'file_size' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
