<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentItem extends Model
{
    protected $fillable = ['name'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(DocumentPhoto::class)->orderBy('id');
    }
}
