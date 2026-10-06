@extends('layouts.app')
@section('title', $document->exists ? 'Edit nota' : 'Tambah nota')
@section('breadcrumb', $document->exists ? 'Edit nota' : 'Tambah nota')
@section('content')
<a class="back-link" href="{{ $document->exists ? route('documents.show', $document) : route('documents.index') }}"><x-icon name="arrow" size="17"/>Kembali ke {{ $document->exists ? 'nota' : 'arsip' }}</a>
<div class="page-heading"><div><div class="eyebrow">{{ $document->exists ? 'PERBARUI ARSIP' : 'NOTA BARU' }}</div><h1>{{ $document->exists ? 'Edit nota' : 'Tambah nota' }}<span class="heading-dot">.</span></h1><p>Simpan nota dengan gambar yang sudah ada. Lengkapi sisanya kapan saja.</p></div><span class="step-pill">Bisa dilengkapi bertahap</span></div>
<form action="{{ $document->exists ? route('documents.update', $document) : route('documents.store') }}" method="post" enctype="multipart/form-data" id="document-form">
    @csrf
    @if($document->exists)@method('PUT')@endif
    <div class="form-layout"><div class="form-main">
        <section class="panel"><div class="panel-heading"><span class="section-number">01</span><div><h2>Informasi nota</h2><p>Identitas kegiatan yang akan tampil pada dokumen cetak.</p></div></div>
            <div class="field"><label for="category">Kategori pembangunan <span>*</span></label>
                <select id="category" name="category" required><option value="">Pilih kategori pembangunan</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(old('category', $document->category) === $category)>{{ $category }}</option>@endforeach</select>
                <small>Belum ada kategori yang sesuai? <a class="text-link" href="{{ route('categories.index') }}" target="_blank" rel="noopener">Kelola kategori</a>. Setelah menambah kategori, muat ulang formulir sebelum mengisi nota.</small>
            </div>
            <div class="fields-row"><div class="field"><label for="receipt_date">Tanggal nota <span>*</span></label><input type="date" id="receipt_date" name="receipt_date" value="{{ old('receipt_date', $document->receipt_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required></div><div class="field"><label for="receipt_number">Nomor nota <span>*</span></label><input id="receipt_number" name="receipt_number" value="{{ old('receipt_number', $document->receipt_number) }}" placeholder="Contoh: NT/2026/001" required maxlength="100"></div></div>
            <div class="receipt-upload">
                <div class="field"><label for="receipt-image">Gambar nota</label><input type="file" id="receipt-image" name="receipt_image" accept="image/jpeg,image/png,image/webp"><small>Unggah foto atau scan nota. JPG, PNG, WebP · Maksimal 5 MB. Bisa ditambahkan nanti.</small></div>
                <p class="upload-error" id="receipt-upload-error" role="alert" hidden></p>
                <div class="receipt-preview" id="receipt-preview" @unless($document->receipt_image_path) hidden @endunless><img id="receipt-preview-image" @if($document->receipt_image_path) src="{{ route('documents.receipt-image', $document) }}" @endif alt="Pratinjau gambar nota"></div>
                @if($document->receipt_image_path)<label class="remove-label receipt-remove"><input type="checkbox" name="remove_receipt_image" id="remove-receipt-image" value="1" @checked(old('remove_receipt_image'))> Hapus gambar nota tersimpan</label><small>Memilih gambar baru akan menggantikan gambar nota sebelumnya.</small>@endif
            </div>
        </section>
        <section class="panel" id="foto-nota"><div class="panel-heading"><span class="section-number">02</span><div><h2>Foto & keterangan</h2><p>Tambahkan gambar ke nota ini. Gambar dan keterangannya bisa dilengkapi nanti.</p></div><span class="count-badge" id="photo-count">{{ $document->exists ? $document->photos->count() : 0 }} foto</span></div>
            <div class="upload-zone" id="upload-zone"><span class="upload-icon"><x-icon name="upload" size="26"/></span><strong>Tarik foto ke sini atau pilih dari perangkat</strong><p>JPG, PNG, WebP · Maks. 5 MB/foto · Maks. 20 foto</p><button type="button" id="choose-photos" class="button secondary">Pilih foto</button><input type="file" id="photo-picker" accept="image/jpeg,image/png,image/webp" multiple hidden><small class="upload-caption-hint">Kolom keterangan muncul otomatis untuk setiap foto yang dipilih.</small></div>
            <div class="upload-error" id="upload-error" role="alert" hidden></div>
            <div class="photo-editor-list" id="photo-list">
                @if($document->exists)
                    @foreach($document->photos as $photo)
                    <div class="photo-editor existing-photo" data-photo-id="{{ $photo->id }}"><img class="photo-preview" src="{{ route('photos.show', $photo) }}" alt="Foto kegiatan {{ $loop->iteration }}"><div class="photo-editor-body"><div class="photo-editor-heading"><strong>Foto tersimpan {{ $loop->iteration }}</strong><label class="remove-label"><input type="checkbox" name="remove_photos[]" value="{{ $photo->id }}" class="remove-existing" @checked(in_array($photo->id, old('remove_photos', [])))> Hapus foto</label></div><label for="caption-{{ $photo->id }}">Keterangan gambar</label><textarea id="caption-{{ $photo->id }}" name="existing[{{ $photo->id }}][caption]" rows="3" maxlength="500">{{ old('existing.'.$photo->id.'.caption', $photo->caption) }}</textarea><small>Opsional. Bisa dilengkapi nanti, maksimal 500 karakter.</small></div></div>
                    @endforeach
                @endif
            </div>
            <p class="photo-empty-hint" id="photo-empty-hint" @if($document->exists && $document->photos->count()) hidden @endif>Belum ada gambar. Nota tetap bisa disimpan dan dilengkapi nanti.</p>
        </section>
        @include('documents.partials.payment-form')
        <div class="form-actions"><span class="muted small">Kolom bertanda <span class="required-star">*</span> wajib diisi.</span><div><a class="button secondary" href="{{ $document->exists ? route('documents.show', $document) : route('documents.index') }}">Batal</a><button class="button primary" type="submit"><x-icon name="check" size="18"/>{{ $document->exists ? 'Simpan perubahan' : 'Simpan nota' }}</button></div></div>
    </div><aside class="form-aside"><div class="guide-card"><span class="guide-icon"><x-icon name="file" size="26"/></span><h3>Satu nota,<br>cerita yang lengkap.</h3><p>Semua gambar dalam nota ini menggunakan kategori dan informasi nota yang sama.</p><hr><div class="guide-step"><span>1</span><p>Isi kategori dan identitas nota.</p></div><div class="guide-step"><span>2</span><p>Tambahkan gambar yang sudah tersedia.</p></div><div class="guide-step"><span>3</span><p>Simpan sekarang. Buka lagi untuk melengkapi atau mencetak.</p></div><div class="guide-bottom"><x-icon name="print" size="17"/>Format A4 · 2 foto per halaman</div></div></aside></div>
</form>
@endsection
