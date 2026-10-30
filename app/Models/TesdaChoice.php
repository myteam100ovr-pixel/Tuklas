<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TesdaChoice extends Model
{
    protected $fillable = ['qualification_id', 'qualification_title', 'nc_level', 'selected_by'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function selector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by');
    }
}
