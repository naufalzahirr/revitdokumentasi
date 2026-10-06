<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentPhoto;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SharedDocumentController extends Controller
{
    public function show(Document $document): Response
    {
        return response()->view('documents.shared', ['document' => $document->load('photos', 'items.photos')])
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function print(Document $document): Response
    {
        return response()->view('documents.print', ['document' => $document->load('photos', 'items.photos'), 'shared' => true])
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function receiptImage(Document $document): StreamedResponse
    {
        abort_unless($document->receipt_image_path, 404);

        return $this->image($document->receipt_image_path);
    }

    public function voucher(Document $document): Response
    {
        return $this->print($document);
    }

    public function photo(Document $document, DocumentPhoto $photo): StreamedResponse
    {
        abort_unless($photo->document_id === $document->id, 404);

        return $this->image($photo->path);
    }

    private function image(string $path): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
