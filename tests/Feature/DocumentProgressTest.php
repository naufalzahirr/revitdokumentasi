<?php

namespace Tests\Feature;

use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signIn();
        Storage::fake('local');
    }

    private function data(array $extra = []): array
    {
        return array_merge([
            'category' => 'Pembangunan Baru - RPS Pengembangan Gim',
            'receipt_date' => '2026-10-07', 'receipt_number' => 'PROGRES-001',
            'amount' => 1000000, 'purpose' => 'Pembelian semen', 'recipient_name' => 'Penerima',
        ], $extra);
    }

    private function completeNote(array $extra = []): Document
    {
        $document = Document::create($this->data($extra));
        $document->receipt_image_path = 'nota.jpg';
        $document->save();
        $item = $document->items()->create(['name' => 'Semen']);
        $document->photos()->create(['document_item_id' => $item->id, 'path' => 'semen.jpg', 'caption' => 'Semen diterima.']);

        return $document;
    }

    private function progress(Document $document): array
    {
        return Document::withProgress()->findOrFail($document->id)->progress();
    }

    public function test_every_item_needs_a_photo_and_every_photo_needs_a_caption(): void
    {
        $document = $this->completeNote();
        $this->assertTrue($this->progress($document)['complete']);
        $this->assertNull($document->recipient_nip);
        $item = $document->items()->create(['name' => 'Pasir']);
        $progress = $this->progress($document);
        $this->assertTrue($progress['data_complete']);
        $this->assertFalse($progress['photos_complete']);
        $this->assertSame(1, $progress['items_without_photos']);
        $this->assertSame(1, $progress['items_with_photos']);
        $photo = $document->photos()->create(['document_item_id' => $item->id, 'path' => 'pasir.jpg', 'caption' => '   ']);
        $progress = $this->progress($document);
        $this->assertSame(2, $progress['items_with_photos']);
        $this->assertSame(1, $progress['photos_without_caption']);
        $this->assertFalse($progress['complete']);
        $this->assertSame([$document->id], Document::progressStatus('photos_incomplete')->pluck('id')->all());
        $this->get(route('documents.index'))->assertOk()->assertSee('1 foto belum memiliki keterangan.')->assertSee('Lengkapi');
        $photo->update(['caption' => 'Pasir yang diterima.']);
        $this->assertTrue($this->progress($document)['complete']);
        $this->assertSame([$document->id], Document::progressStatus('complete')->pluck('id')->all());
    }

    public function test_empty_notes_show_missing_fields_and_items(): void
    {
        $document = Document::create($this->data(['amount' => null, 'purpose' => '', 'recipient_name' => null]));
        $progress = $this->progress($document);
        $this->assertSame(3, $progress['data_done']);
        $this->assertSame(7, $progress['data_total']);
        $this->assertSame(['gambar nota', 'keperluan', 'penerima pembayaran', 'nominal'], $progress['missing_data']);
        $this->assertSame(0, $progress['items']);
        $this->assertFalse($progress['complete']);
        foreach (['incomplete', 'data_incomplete', 'photos_incomplete'] as $status) {
            $this->assertSame([$document->id], Document::progressStatus($status)->pluck('id')->all());
        }
        $this->get(route('documents.index'))->assertOk()->assertSee('Item dan foto belum ditambahkan.')
            ->assertSee('Belum diisi: gambar nota, keperluan, penerima pembayaran, nominal.');
    }

    public function test_missing_data_filters_agree_with_card_status_and_nip_is_optional(): void
    {
        $document = $this->completeNote();
        foreach (['category', 'receipt_number', 'receipt_image_path', 'purpose', 'recipient_name', 'amount'] as $field) {
            $original = $document->$field;
            $document->$field = $field === 'amount' ? 0 : '   ';
            $document->save();
            $this->assertFalse($this->progress($document)['data_complete']);
            $this->assertSame(6, $this->progress($document)['data_done']);
            $this->assertSame([$document->id], Document::dataIncomplete()->pluck('id')->all());
            $this->assertSame([], Document::progressStatus('complete')->pluck('id')->all());
            $document->$field = $original;
            $document->save();
        }
        $this->assertTrue($this->progress($document)['complete']);
        $this->assertSame([], Document::dataIncomplete()->pluck('id')->all());
    }

    public function test_summary_counts_all_notes_across_pages_and_filters_combine_with_search_and_category(): void
    {
        $complete = $this->completeNote(['receipt_number' => 'SELESAI-001']);
        $photoMissing = $this->completeNote(['receipt_number' => 'FOTO-001']);
        $photoMissing->items()->create(['name' => 'Pasir']);
        $blanks = [];
        foreach (range(1, 10) as $number) {
            $blanks[] = Document::create($this->data(['receipt_number' => 'BELUM-'.$number]));
        }
        $response = $this->get(route('documents.index'))->assertOk();
        $response->assertViewHas('stats', fn ($stats) => $stats['documents'] === 12 && $stats['complete'] === 1
            && $stats['incomplete'] === 11 && $stats['percentage'] === 8 && $stats['data_complete'] === 2 && $stats['photos_complete'] === 1);
        $response->assertViewHas('documents', fn ($documents) => $documents->total() === 12 && $documents->count() === 9);
        foreach (['complete' => 1, 'incomplete' => 11, 'data_incomplete' => 10, 'photos_incomplete' => 11] as $status => $count) {
            $this->get(route('documents.index', ['status' => $status]))->assertOk()
                ->assertViewHas('documents', fn ($documents) => $documents->total() === $count)
                ->assertViewHas('stats', fn ($stats) => $stats['documents'] === 12 && $stats['complete'] === 1);
        }
        $this->get(route('documents.index', ['status' => 'incomplete', 'q' => 'FOTO', 'category' => $photoMissing->category]))
            ->assertOk()->assertViewHas('documents', fn ($documents) => $documents->pluck('id')->all() === [$photoMissing->id]);
        $this->get(route('documents.index', ['status' => 'incomplete', 'q' => 'SELESAI']))->assertOk()
            ->assertSee('Nota belum ditemukan')->assertViewHas('documents', fn ($documents) => $documents->isEmpty());
        $this->get(route('documents.index', ['status' => 'incomplete', 'q' => 'BELUM', 'category' => $complete->category]))
            ->assertOk()->assertViewHas('documents', fn ($documents) => str_contains($documents->nextPageUrl(), 'status=incomplete')
                && str_contains($documents->nextPageUrl(), 'q=BELUM') && str_contains($documents->nextPageUrl(), 'category='));
    }

    public function test_progress_updates_after_photo_caption_receipt_and_payment_changes(): void
    {
        $this->post(route('documents.store'), $this->data(['amount' => null, 'purpose' => '', 'recipient_name' => '']))->assertSessionHasNoErrors();
        $document = Document::firstOrFail();
        $this->assertFalse($this->progress($document)['complete']);
        $this->put(route('documents.update', $document), $this->data([
            'receipt_image' => UploadedFile::fake()->image('nota.jpg'),
            'items' => [['name' => 'Semen', 'photos' => [['file' => UploadedFile::fake()->image('semen.jpg'), 'caption' => 'Semen diterima.']]]],
        ]))->assertSessionHasNoErrors();
        $this->assertTrue($this->progress($document)['complete']);
        $this->get(route('documents.index', ['status' => 'complete']))->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['complete'] === 1 && $stats['percentage'] === 100);
        $item = $document->fresh()->items->first();
        $photo = $item->photos->first();
        $this->put(route('documents.update', $document), $this->data([
            'items' => [['id' => $item->id, 'name' => $item->name, 'existing' => [$photo->id => ['caption' => '']]]],
        ]))->assertSessionHasNoErrors();
        $this->assertFalse($this->progress($document)['photos_complete']);
        $this->put(route('documents.update', $document), $this->data(['remove_photos' => [$photo->id], 'remove_receipt_image' => 1]))->assertSessionHasNoErrors();
        $this->assertSame(1, $this->progress($document)['items_without_photos']);
        $this->assertFalse($this->progress($document)['data_complete']);
        $this->delete(route('documents.destroy', $document))->assertRedirect();
        $this->get(route('documents.index'))->assertOk()->assertViewHas('stats', fn ($stats) => $stats['documents'] === 0
            && $stats['complete'] === 0 && $stats['percentage'] === 0);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->get(route('documents.index', ['status' => 'unknown']))->assertSessionHasErrors('status');
    }
}
