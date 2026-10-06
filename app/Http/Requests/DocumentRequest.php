<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DocumentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->boolean('items_present') && ! $this->has('items')) {
            $this->merge(['items' => []]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $document = $this->route('document');

        return [
            'category' => ['required', 'string', 'max:100', Rule::exists('categories', 'name')],
            'receipt_date' => ['required', 'date_format:Y-m-d'],
            'receipt_number' => ['required', 'string', 'max:100'],
            'receipt_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=8000,max_height=8000'],
            'remove_receipt_image' => ['sometimes', 'boolean'],
            'amount' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'purpose' => ['nullable', 'string', 'max:1000'],
            'recipient_name' => ['nullable', 'string', 'max:150'],
            'recipient_nip' => ['nullable', 'string', 'max:32'],
            'items_present' => ['sometimes', 'boolean'],
            'items' => ['sometimes', 'array', 'max:50'],
            'items.*' => ['array:id,name,photos,existing,remove_photos'],
            'items.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('document_items', 'id')->where('document_id', $document?->id ?? 0)],
            'items.*.name' => ['required', 'string', 'max:200'],
            'items.*.photos' => ['sometimes', 'array', 'max:20'],
            'items.*.photos.*' => ['array:file,caption'],
            'items.*.photos.*.file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=8000,max_height=8000'],
            'items.*.photos.*.caption' => ['nullable', 'string', 'max:500'],
            'items.*.existing' => ['sometimes', 'array'],
            'items.*.existing.*' => ['array:caption'],
            'items.*.existing.*.caption' => ['nullable', 'string', 'max:500'],
            'items.*.remove_photos' => ['sometimes', 'array'],
            'items.*.remove_photos.*' => ['integer', 'distinct'],
            'remove_items' => ['sometimes', 'array', 'max:50'],
            'remove_items.*' => ['integer', 'distinct', Rule::exists('document_items', 'id')->where('document_id', $document?->id ?? 0)],
            'photos' => ['sometimes', 'array', 'max:20'],
            'photos.*' => ['array:file,caption'],
            'photos.*.file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=8000,max_height=8000'],
            'photos.*.caption' => ['nullable', 'string', 'max:500'],
            'existing' => ['sometimes', 'array'],
            'existing.*' => ['array:caption'],
            'existing.*.caption' => ['nullable', 'string', 'max:500'],
            'remove_photos' => ['sometimes', 'array'],
            'remove_photos.*' => ['integer', 'distinct', Rule::exists('document_photos', 'id')->where('document_id', $document?->id ?? 0)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $document = $this->route('document');
            $currentIds = $document instanceof Document ? $document->photos()->pluck('id')->all() : [];
            $removed = $this->input('remove_photos', []);
            $existing = $this->input('existing', []);
            if (! is_array($removed) || ! is_array($existing)) {
                return;
            }
            foreach (array_keys($existing) as $id) {
                if (! in_array((string) $id, array_map('strval', $currentIds), true)) {
                    $validator->errors()->add('existing', 'Foto tidak ditemukan dalam nota ini.');
                }
            }
            $uploads = $this->file('photos', []);
            $items = $this->input('items', []);
            $removedItems = $this->input('remove_items', []);
            $currentItems = $document instanceof Document ? $document->items()->with('photos')->get()->keyBy('id') : collect();
            $newItemCount = count($uploads) > 0 && ! $currentItems->contains('name', 'Dokumentasi sebelumnya') ? 1 : 0;
            $newPhotoCount = is_array($uploads) ? count($uploads) : 0;
            $removedPhotoIds = $removed;
            foreach ($removedItems as $id) {
                $removedPhotoIds = array_merge($removedPhotoIds, $currentItems->get($id)?->photos->pluck('id')->all() ?? []);
            }
            foreach ($items as $key => $item) {
                $id = $item['id'] ?? null;
                if ($id && in_array((string) $id, array_map('strval', $removedItems), true)) {
                    $validator->errors()->add("items.{$key}.id", 'Item yang dihapus tidak dapat diubah bersamaan.');
                }
                $itemPhotoIds = $id ? ($currentItems->get($id)?->photos->pluck('id')->all() ?? []) : [];
                $changedPhotoIds = array_merge(array_keys($item['existing'] ?? []), $item['remove_photos'] ?? []);
                foreach ($changedPhotoIds as $photoId) {
                    if (! in_array((string) $photoId, array_map('strval', $itemPhotoIds), true)) {
                        $validator->errors()->add("items.{$key}.existing", 'Foto tidak ditemukan dalam item ini.');
                    }
                }
                $removedPhotoIds = array_merge($removedPhotoIds, $item['remove_photos'] ?? []);
                $newPhotoCount += count($this->file("items.{$key}.photos", []));
                if (! $id) {
                    $newItemCount++;
                }
            }
            if ($currentItems->count() - count($removedItems) + $newItemCount > 50) {
                $validator->errors()->add('items', 'Maksimal 50 item dalam satu nota.');
            }
            $remaining = count(array_diff($currentIds, $removedPhotoIds)) + $newPhotoCount;
            if ($remaining > 20) {
                $validator->errors()->add('photos', 'Maksimal 20 foto dalam satu nota.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'category.exists' => 'Pilih kategori yang tersedia. Tambahkan kategori baru melalui menu Kategori.',
            'items.*.id.exists' => 'Item tidak ditemukan dalam nota ini.',
            'items.*.id.distinct' => 'Item yang sama tidak boleh dikirim dua kali.',
            'remove_items.*.exists' => 'Item tidak ditemukan dalam nota ini.',
            'items.*.photos.*.file.image' => 'Berkas yang diunggah harus berupa gambar.',
            'items.*.photos.*.file.mimes' => 'Gunakan foto JPG, PNG, atau WebP.',
            'items.*.photos.*.file.max' => 'Ukuran setiap foto maksimal 5 MB.',
            'items.*.photos.*.file.dimensions' => 'Dimensi foto maksimal 8.000 × 8.000 piksel.',
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max.string' => ':attribute maksimal :max karakter.',
            'max.array' => ':attribute maksimal :max item.',
            'photos.*.file.image' => 'Berkas yang diunggah harus berupa gambar.',
            'photos.*.file.mimes' => 'Gunakan foto JPG, PNG, atau WebP.',
            'photos.*.file.max' => 'Ukuran setiap foto maksimal 5 MB.',
            'photos.*.file.dimensions' => 'Dimensi foto maksimal 8.000 × 8.000 piksel.',
            'receipt_date.date_format' => 'Tanggal nota tidak valid.',
            'receipt_image.image' => 'Gambar nota harus berupa gambar.',
            'receipt_image.mimes' => 'Gambar nota harus JPG, PNG, atau WebP.',
            'receipt_image.max' => 'Ukuran gambar nota maksimal 5 MB.',
            'receipt_image.dimensions' => 'Dimensi gambar nota maksimal 8.000 × 8.000 piksel.',
            'amount.integer' => 'Nominal harus berupa rupiah bulat, tanpa titik atau koma.',
            'amount.min' => 'Nominal tidak boleh negatif.',
            'amount.max' => 'Nominal maksimal Rp999.999.999.999.',
            'remove_photos.*.exists' => 'Foto tidak ditemukan dalam nota ini.',
        ];
    }

    public function attributes(): array
    {
        return [
            'category' => 'Kategori pembangunan', 'receipt_date' => 'Tanggal nota',
            'receipt_number' => 'Nomor nota', 'photos' => 'Foto',
            'receipt_image' => 'Gambar nota',
            'amount' => 'Nominal', 'purpose' => 'Keperluan',
            'recipient_name' => 'Nama penerima pembayaran',
            'recipient_nip' => 'NIP penerima pembayaran',
            'items' => 'Item nota', 'items.*.name' => 'Nama item',
            'items.*.photos.*.file' => 'Foto item', 'items.*.photos.*.caption' => 'Keterangan gambar',
            'items.*.existing.*.caption' => 'Keterangan gambar',
            'photos.*.file' => 'Foto', 'photos.*.caption' => 'Keterangan gambar',
            'existing.*.caption' => 'Keterangan gambar',
        ];
    }
}
