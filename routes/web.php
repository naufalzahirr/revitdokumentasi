<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SharedDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DocumentController::class, 'index'])->name('home');
Route::resource('kategori', CategoryController::class)->parameters(['kategori' => 'category'])->names('categories')->except(['create', 'show']);
Route::get('/dokumen/{document}/cetak', [DocumentController::class, 'print'])->name('documents.print');
Route::get('/dokumen/{document}/cetak-bukti', [DocumentController::class, 'voucher'])->name('documents.voucher');
Route::get('/foto/{photo}', [DocumentController::class, 'photo'])->name('photos.show');
Route::get('/dokumen/{document}/gambar-nota', [DocumentController::class, 'receiptImage'])->name('documents.receipt-image');
Route::post('/dokumen/{document}/bagikan', [DocumentController::class, 'share'])->name('documents.share');
Route::delete('/dokumen/{document}/bagikan', [DocumentController::class, 'unshare'])->name('documents.unshare');
Route::prefix('bagikan/{document:share_token}')->name('shared.')->group(function () {
    Route::get('/', [SharedDocumentController::class, 'show'])->name('show');
    Route::get('/cetak', [SharedDocumentController::class, 'print'])->name('print');
    Route::get('/cetak-bukti', [SharedDocumentController::class, 'voucher'])->name('voucher');
    Route::get('/gambar-nota', [SharedDocumentController::class, 'receiptImage'])->name('receipt-image');
    Route::get('/foto/{photo}', [SharedDocumentController::class, 'photo'])->name('photo');
});
Route::resource('dokumen', DocumentController::class)->parameters(['dokumen' => 'document'])->names('documents');
