@extends('layouts.app')
@section('title', 'Edit kategori')
@section('breadcrumb', 'Edit kategori')
@section('content')
<a class="back-link" href="{{ route('categories.index') }}"><x-icon name="arrow" size="17"/>Kembali ke kategori</a>
<div class="page-heading"><div><h1>Edit kategori<span class="heading-dot">.</span></h1><p>Perubahan nama juga diterapkan pada nota yang menggunakan kategori ini.</p></div></div>
<section class="panel category-edit"><form action="{{ route('categories.update', $category) }}" method="post">@csrf @method('PUT')
    <div class="field"><label for="name">Nama kategori <span>*</span></label><input id="name" name="name" value="{{ old('name', $category->name) }}" required maxlength="100"></div>
    <div class="heading-actions"><a class="button secondary" href="{{ route('categories.index') }}">Batal</a><button class="button primary" type="submit">Simpan perubahan</button></div>
</form></section>
@endsection
