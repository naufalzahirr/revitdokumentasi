<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentPhoto extends Model
{
    protected $fillable = ['path', 'caption', 'document_item_id'];

    protected static function booted(): void
    {
        static::creating(function (DocumentPhoto $photo) {
            if (! $photo->document_item_id) {
                $photo->document_item_id = $photo->document()->firstOrFail()->items()->firstOrCreate(['name' => 'Dokumentasi sebelumnya'])->id;
            }
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(DocumentItem::class, 'document_item_id');
    }
}
