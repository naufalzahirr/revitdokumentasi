<?php

namespace App\Models;

use App\Support\VoucherNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    public const EDITABLE_FIELDS = ['category', 'receipt_date', 'receipt_number', 'amount', 'purpose', 'recipient_name', 'recipient_nip'];

    protected $fillable = self::EDITABLE_FIELDS;

    protected $hidden = ['receipt_image_path', 'share_token'];

    protected static function booted(): void
    {
        static::saving(function (Document $document) {
            $document->forceFill(config('voucher.fixed_fields'));
            $document->payment_date = $document->receipt_date;
            if ($document->exists) {
                $document->voucher_number = $document->getRawOriginal('voucher_number');
            }
        });
        static::creating(function (Document $document) {
            $document->voucher_number = VoucherNumber::next();
        });
    }

    protected function casts(): array
    {
        return ['receipt_date' => 'date', 'payment_date' => 'date', 'amount' => 'integer'];
    }

    public function photos(): HasMany
    {
        return $this->hasMany(DocumentPhoto::class)->orderBy('id');
    }

    public function coverPhoto(): HasOne
    {
        return $this->hasOne(DocumentPhoto::class)->oldestOfMany();
    }
}
