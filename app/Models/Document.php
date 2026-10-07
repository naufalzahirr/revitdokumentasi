<?php

namespace App\Models;

use App\Support\VoucherNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    public const EDITABLE_FIELDS = ['category', 'receipt_date', 'receipt_number', 'amount', 'purpose', 'recipient_name', 'recipient_nip'];

    private const PROGRESS_FIELDS = [
        'category' => 'kategori', 'receipt_date' => 'tanggal nota', 'receipt_number' => 'nomor nota',
        'receipt_image_path' => 'gambar nota', 'purpose' => 'keperluan', 'recipient_name' => 'penerima pembayaran',
    ];

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

    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class)->orderBy('id');
    }

    public function coverPhoto(): HasOne
    {
        return $this->hasOne(DocumentPhoto::class)->oldestOfMany();
    }

    public function scopeWithProgress(Builder $query): void
    {
        $query->withCount([
            'photos', 'items',
            'items as items_without_photos_count' => fn (Builder $items) => $items->whereDoesntHave('photos'),
            'photos as photos_without_caption_count' => fn (Builder $photos) => $photos->withoutCaption(),
        ]);
    }

    public function scopeDataComplete(Builder $query): void
    {
        foreach (array_keys(self::PROGRESS_FIELDS) as $field) {
            $query->whereNotNull($field)->whereRaw("TRIM({$field}) <> ''");
        }
        $query->where('amount', '>', 0);
    }

    public function scopeDataIncomplete(Builder $query): void
    {
        $query->where(function (Builder $missing) {
            foreach (array_keys(self::PROGRESS_FIELDS) as $field) {
                $missing->orWhereNull($field)->orWhereRaw("TRIM({$field}) = ''");
            }
            $missing->orWhereNull('amount')->orWhere('amount', '<=', 0);
        });
    }

    public function scopePhotosComplete(Builder $query): void
    {
        $query->whereHas('items')
            ->whereDoesntHave('items', fn (Builder $items) => $items->whereDoesntHave('photos'))
            ->whereDoesntHave('photos', fn (Builder $photos) => $photos->withoutCaption());
    }

    public function scopePhotosIncomplete(Builder $query): void
    {
        $query->where(fn (Builder $missing) => $missing->whereDoesntHave('items')
            ->orWhereHas('items', fn (Builder $items) => $items->whereDoesntHave('photos'))
            ->orWhereHas('photos', fn (Builder $photos) => $photos->withoutCaption()));
    }

    public function scopeProgressStatus(Builder $query, ?string $status): void
    {
        match ($status) {
            'complete' => $query->dataComplete()->photosComplete(),
            'incomplete' => $query->where(fn (Builder $missing) => $missing->dataIncomplete()->orWhere(fn (Builder $photos) => $photos->photosIncomplete())),
            'data_incomplete' => $query->dataIncomplete(),
            'photos_incomplete' => $query->photosIncomplete(),
            default => null,
        };
    }

    public function progress(): array
    {
        $missing = [];
        foreach (self::PROGRESS_FIELDS as $field => $label) {
            if (blank($this->$field)) {
                $missing[] = $label;
            }
        }
        if (! $this->amount || $this->amount <= 0) {
            $missing[] = 'nominal';
        }
        $items = (int) $this->items_count;
        $withoutPhotos = (int) $this->items_without_photos_count;
        $withoutCaption = (int) $this->photos_without_caption_count;
        $photosComplete = $items > 0 && $withoutPhotos === 0 && $withoutCaption === 0;

        return [
            'complete' => $missing === [] && $photosComplete,
            'data_complete' => $missing === [], 'missing_data' => $missing,
            'data_done' => count(self::PROGRESS_FIELDS) + 1 - count($missing),
            'data_total' => count(self::PROGRESS_FIELDS) + 1,
            'photos_complete' => $photosComplete, 'items' => $items,
            'items_with_photos' => $items - $withoutPhotos,
            'items_without_photos' => $withoutPhotos, 'photos_without_caption' => $withoutCaption,
        ];
    }
}
