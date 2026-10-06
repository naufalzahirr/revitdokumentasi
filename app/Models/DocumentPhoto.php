<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentPhoto extends Model
{
    protected $fillable = ['path', 'caption'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
