<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DocumentRequest extends FormRequest
{
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
            $remaining = count(array_diff($currentIds, $removed)) + (is_array($uploads) ? count($uploads) : 0);
            if ($remaining > 20) {
                $validator->errors()->add('photos', 'Maksimal 20 foto dalam satu nota.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'category.exists' => 'Pilih kategori yang tersedia. Tambahkan kategori baru melalui menu Kategori.',
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
            'photos.*.file' => 'Foto', 'photos.*.caption' => 'Keterangan gambar',
            'existing.*.caption' => 'Keterangan gambar',
        ];
    }
}
