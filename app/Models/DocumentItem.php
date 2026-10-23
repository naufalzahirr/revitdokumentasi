<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentItem extends Model
{
    public const EDITABLE_FIELDS = ['name', 'ordered_quantity', 'received_good_quantity', 'received_damaged_quantity'];

    protected $fillable = self::EDITABLE_FIELDS;

    protected function casts(): array
    {
        return ['ordered_quantity' => 'float', 'received_good_quantity' => 'float', 'received_damaged_quantity' => 'float'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(DocumentPhoto::class)->orderBy('id');
    }
}
