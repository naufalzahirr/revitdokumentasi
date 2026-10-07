<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentRequest;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentPhoto;
use App\Support\VoucherNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['complete', 'incomplete', 'data_incomplete', 'photos_incomplete'])],
        ]);
        $query = Document::with('coverPhoto')->withProgress()->progressStatus($filters['status'] ?? null)->latest();
        if ($search = trim($filters['q'] ?? '')) {
            $query->where(function ($query) use ($search) {
                $query->where('receipt_number', 'like', "%{$search}%")->orWhere('category', 'like', "%{$search}%");
            });
        }
        if ($category = $filters['category'] ?? '') {
            $query->where('category', $category);
        }

        $total = Document::count();
        $complete = Document::progressStatus('complete')->count();

        return view('documents.index', [
            'documents' => $query->paginate(9)->withQueryString(),
            'categories' => $this->categories(),
            'stats' => [
                'documents' => $total, 'photos' => DocumentPhoto::count(), 'categories' => Category::count(),
                'complete' => $complete, 'incomplete' => $total - $complete,
                'percentage' => $total > 0 ? (int) floor($complete / $total * 100) : 0,
                'data_complete' => Document::dataComplete()->count(), 'photos_complete' => Document::photosComplete()->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('documents.form', ['document' => new Document, 'categories' => $this->categories(), 'nextVoucherNumber' => VoucherNumber::preview()]);
    }

    public function store(DocumentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $paths = [];
        try {
            $document = DB::transaction(function () use ($data, &$paths) {
                $document = Document::create(collect($data)->only(Document::EDITABLE_FIELDS)->all());
                $removedPaths = [];
                $this->syncReceiptImage($document, $data, $paths, $removedPaths);
                $this->syncItems($document, $data, $paths, $removedPaths);
                $this->addPhotos($document, $data['photos'] ?? [], $paths);

                return $document;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }

        return redirect()->route('documents.show', $document)->with('success', 'Nota berhasil disimpan. Gambar dan keterangan bisa ditambahkan kapan saja.');
    }

    public function show(Document $document): View
    {
        return view('documents.show', ['document' => $document->load('photos', 'items.photos')]);
    }

    public function edit(Document $document): View
    {
        return view('documents.form', ['document' => $document->load('photos', 'items.photos'), 'categories' => $this->categories()]);
    }

    public function update(DocumentRequest $request, Document $document): RedirectResponse
    {
        $data = $request->validated();
        $paths = [];
        $removedPaths = [];
        try {
            DB::transaction(function () use ($document, $data, &$paths, &$removedPaths) {
                $document->update(collect($data)->only(Document::EDITABLE_FIELDS)->all());
                $this->syncReceiptImage($document, $data, $paths, $removedPaths);
                $removed = $document->photos()->whereIn('id', $data['remove_photos'] ?? [])->get();
                $removedPaths = array_merge($removedPaths, $removed->pluck('path')->all());
                foreach ($removed as $photo) {
                    $photo->delete();
                }
                foreach ($data['existing'] ?? [] as $id => $attributes) {
                    $document->photos()->whereKey($id)->update(['caption' => $attributes['caption'] ?? '']);
                }
                $this->syncItems($document, $data, $paths, $removedPaths);
                $this->addPhotos($document, $data['photos'] ?? [], $paths);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }
        Storage::disk('local')->delete($removedPaths);

        return redirect()->route('documents.show', $document)->with('success', 'Perubahan nota berhasil disimpan.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $paths = $document->photos()->pluck('path')->all();
        if ($document->receipt_image_path) {
            $paths[] = $document->receipt_image_path;
        }
        DB::transaction(fn () => $document->delete());
        Storage::disk('local')->delete($paths);

        return redirect()->route('documents.index')->with('success', 'Nota dan gambar-gambarnya berhasil dihapus.');
    }

    public function print(Document $document): View
    {
        return view('documents.print', ['document' => $document->load('photos', 'items.photos')]);
    }

    public function voucher(Document $document): View
    {
        return $this->print($document);
    }

    public function photo(DocumentPhoto $photo): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($photo->path), 404);

        return Storage::disk('local')->response($photo->path, null, ['Cache-Control' => 'private, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function receiptImage(Document $document): StreamedResponse
    {
        abort_unless($document->receipt_image_path && Storage::disk('local')->exists($document->receipt_image_path), 404);

        return Storage::disk('local')->response($document->receipt_image_path, null, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function share(Document $document): RedirectResponse
    {
        if (! $document->share_token) {
            $document->share_token = Str::random(48);
            $document->save();
        }

        return redirect()->route('documents.show', $document)->with('success', 'Tautan berbagi siap disalin. Penerima dapat melihat nota beserta foto dan keterangannya.');
    }

    public function unshare(Document $document): RedirectResponse
    {
        $document->share_token = null;
        $document->save();

        return redirect()->route('documents.show', $document)->with('success', 'Tautan berbagi dinonaktifkan. Tautan lama tidak bisa dibuka lagi.');
    }

    private function syncReceiptImage(Document $document, array $data, array &$paths, array &$removedPaths): void
    {
        if (! isset($data['receipt_image']) && ! ($data['remove_receipt_image'] ?? false)) {
            return;
        }
        if ($document->receipt_image_path) {
            $removedPaths[] = $document->receipt_image_path;
        }
        $path = null;
        if (isset($data['receipt_image'])) {
            $path = $data['receipt_image']->store('documents/'.$document->id.'/receipt', 'local');
            if ($path === false) {
                throw new \RuntimeException('Gambar nota gagal disimpan. Periksa izin folder storage.');
            }
            $paths[] = $path;
        }
        $document->receipt_image_path = $path;
        $document->save();
    }

    private function syncItems(Document $document, array $data, array &$paths, array &$removedPaths): void
    {
        foreach ($document->items()->whereIn('id', $data['remove_items'] ?? [])->with('photos')->get() as $item) {
            $removedPaths = array_merge($removedPaths, $item->photos->pluck('path')->all());
            $item->delete();
        }
        foreach ($data['items'] ?? [] as $attributes) {
            if ($attributes['id'] ?? null) {
                $item = $document->items()->whereKey($attributes['id'])->firstOrFail();
                $item->update(['name' => $attributes['name']]);
            } else {
                $item = $document->items()->create(['name' => $attributes['name']]);
            }
            foreach ($item->photos()->whereIn('id', $attributes['remove_photos'] ?? [])->get() as $photo) {
                $removedPaths[] = $photo->path;
                $photo->delete();
            }
            foreach ($attributes['existing'] ?? [] as $id => $photo) {
                $item->photos()->whereKey($id)->update(['caption' => $photo['caption'] ?? '']);
            }
            $this->addPhotos($document, $attributes['photos'] ?? [], $paths, $item);
        }
    }

    private function addPhotos(Document $document, array $photos, array &$paths, ?DocumentItem $item = null): void
    {
        foreach ($photos as $photo) {
            $path = $photo['file']->store('documents/'.$document->id, 'local');
            if ($path === false) {
                throw new \RuntimeException('Foto gagal disimpan. Periksa izin folder storage.');
            }
            $paths[] = $path;
            $document->photos()->create(['path' => $path, 'caption' => $photo['caption'] ?? '', 'document_item_id' => $item?->id]);
        }
    }

    private function categories(): array
    {
        return Category::orderBy('name')->pluck('name')->all();
    }
}
