<?php

namespace Tests\Feature;

use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->signIn();
    }

    private function data(array $overrides = []): array
    {
        return array_replace([
            'category' => 'Renovasi atap', 'receipt_date' => '2026-10-06', 'receipt_number' => 'NT/2026/001',
            'photos' => [
                ['file' => UploadedFile::fake()->image('atap.jpg'), 'caption' => 'Pemasangan rangka atap.'],
                ['file' => UploadedFile::fake()->image('kelas.png'), 'caption' => 'Perbaikan ruang kelas.'],
            ],
        ], $overrides);
    }

    private function createDocument(): Document
    {
        $this->post(route('documents.store'), $this->data())->assertSessionHasNoErrors();

        return Document::latest('id')->firstOrFail()->load('photos');
    }

    public function test_document_and_multiple_photos_are_saved_and_viewable(): void
    {
        $document = $this->createDocument();
        $this->assertDatabaseHas('documents', ['category' => 'Renovasi atap', 'receipt_number' => 'NT/2026/001']);
        $this->assertCount(2, $document->photos);
        foreach ($document->photos as $photo) {
            Storage::disk('local')->assertExists($photo->path);
            $this->get(route('photos.show', $photo))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        }
        $this->get(route('documents.show', $document))->assertOk()->assertSee('Pemasangan rangka atap.')->assertSee('06 Oktober 2026');
        $this->get(route('documents.edit', $document))->assertOk()->assertSee('Edit nota');
    }

    public function test_receipt_data_is_required_but_photos_can_be_added_later(): void
    {
        $this->post(route('documents.store'), [])->assertSessionHasErrors(['category', 'receipt_date', 'receipt_number']);
        $this->assertDatabaseCount('documents', 0);
        $this->post(route('documents.store'), $this->data(['photos' => []]))->assertSessionHasNoErrors();
        $document = Document::firstOrFail();
        $this->assertDatabaseCount('documents', 1);
        $this->assertCount(0, $document->photos);
        $this->get(route('documents.show', $document))->assertOk()->assertSee('Nota tersimpan, gambar bisa menyusul.')->assertSee('Edit nota');
        $this->get(route('documents.print', $document))->assertOk()->assertSee('Belum ada gambar dalam nota ini.')->assertSee('Halaman 3 dari 3');
    }

    public function test_non_images_oversized_photos_and_long_captions_are_rejected(): void
    {
        $this->post(route('documents.store'), $this->data(['photos' => [
            ['file' => UploadedFile::fake()->create('payload.svg', 1, 'image/svg+xml'), 'caption' => 'Berkas tidak valid'],
        ]]))->assertSessionHasErrors('photos.0.file');
        $this->post(route('documents.store'), $this->data(['photos' => [
            ['file' => UploadedFile::fake()->image('besar.jpg')->size(5121), 'caption' => 'Foto terlalu besar'],
        ]]))->assertSessionHasErrors('photos.0.file');
        $this->post(route('documents.store'), $this->data(['photos' => [
            ['file' => UploadedFile::fake()->image('foto.jpg'), 'caption' => str_repeat('a', 501)],
        ]]))->assertSessionHasErrors('photos.0.caption');
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_impossible_date_is_rejected(): void
    {
        $this->post(route('documents.store'), $this->data(['receipt_date' => '2026-02-30']))->assertSessionHasErrors('receipt_date');
    }

    public function test_update_changes_captions_adds_photos_and_removes_old_files(): void
    {
        $document = $this->createDocument();
        [$keep, $remove] = $document->photos;
        $this->put(route('documents.update', $document), $this->data([
            'category' => 'Pembangunan ruang kelas',
            'existing' => [$keep->id => ['caption' => 'Keterangan diperbarui.']],
            'remove_photos' => [$remove->id],
            'photos' => [['file' => UploadedFile::fake()->image('baru.webp'), 'caption' => 'Hasil pembangunan.']],
        ]))->assertRedirect(route('documents.show', $document))->assertSessionHasNoErrors();
        $this->assertCount(2, $document->fresh()->photos);
        $this->assertDatabaseHas('document_photos', ['id' => $keep->id, 'caption' => 'Keterangan diperbarui.']);
        $this->assertDatabaseMissing('document_photos', ['id' => $remove->id]);
        Storage::disk('local')->assertMissing($remove->path);
        Storage::disk('local')->assertExists($keep->path);
    }

    public function test_all_photos_can_be_removed_while_preserving_the_receipt(): void
    {
        $document = $this->createDocument();
        $this->put(route('documents.update', $document), $this->data([
            'photos' => [], 'remove_photos' => $document->photos->pluck('id')->all(),
        ]))->assertSessionHasNoErrors();
        $this->assertCount(0, $document->fresh()->photos);
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'receipt_number' => 'NT/2026/001']);
        foreach ($document->photos as $photo) {
            Storage::disk('local')->assertMissing($photo->path);
        }
    }

    public function test_photos_can_be_added_in_multiple_edits_to_the_same_receipt(): void
    {
        $data = collect($this->data())->except('photos')->all();
        $this->post(route('documents.store'), $data)->assertSessionHasNoErrors();
        $document = Document::firstOrFail();
        $this->put(route('documents.update', $document), $data + ['photos' => [
            ['file' => UploadedFile::fake()->image('pertama.jpg')],
        ]])->assertSessionHasNoErrors();
        $first = $document->fresh()->photos->firstOrFail();
        $this->assertSame('', $first->caption);
        $this->put(route('documents.update', $document), $data + [
            'photos' => [['file' => UploadedFile::fake()->image('kedua.jpg'), 'caption' => null]],
            'existing' => [$first->id => ['caption' => 'Gambar pertama dilengkapi belakangan.']],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('documents', 1);
        $this->assertCount(2, $document->fresh()->photos);
        $this->assertSame('Gambar pertama dilengkapi belakangan.', $first->fresh()->caption);
        Storage::disk('local')->assertExists($first->path);
        $this->get(route('documents.print', $document))->assertOk()->assertSee('Gambar 1')->assertSee('Gambar 2')->assertSee('Keterangan belum diisi.');
        $this->put(route('documents.update', $document), $data + ['existing' => [
            $first->id => ['caption' => null],
        ]])->assertSessionHasNoErrors();
        $this->assertSame('', $first->fresh()->caption);
    }

    public function test_cannot_change_or_remove_another_documents_photos(): void
    {
        $first = $this->createDocument();
        $second = $this->createDocument();
        $foreign = $second->photos->first();
        $this->put(route('documents.update', $first), $this->data([
            'photos' => [], 'remove_photos' => [$foreign->id],
        ]))->assertSessionHasErrors('remove_photos.0');
        $this->put(route('documents.update', $first), $this->data([
            'photos' => [], 'existing' => [$foreign->id => ['caption' => 'Dilarang berubah']],
        ]))->assertSessionHasErrors('existing');
        $this->assertSame('Pemasangan rangka atap.', $foreign->fresh()->caption);
        Storage::disk('local')->assertExists($foreign->path);
    }

    public function test_total_photo_limit_applies_to_new_and_existing_photos(): void
    {
        $document = $this->createDocument();
        $photos = array_map(fn () => ['file' => UploadedFile::fake()->image('foto.jpg'), 'caption' => 'Foto kegiatan.'], range(1, 19));
        $this->put(route('documents.update', $document), $this->data(['photos' => $photos]))->assertSessionHasErrors('photos');
        $this->assertCount(2, $document->fresh()->photos);
    }

    public function test_delete_removes_document_photo_records_and_files(): void
    {
        $document = $this->createDocument();
        $this->delete(route('documents.destroy', $document))->assertRedirect(route('documents.index'));
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_photos', 0);
        foreach ($document->photos as $photo) {
            Storage::disk('local')->assertMissing($photo->path);
            $this->get(route('photos.show', $photo))->assertNotFound();
        }
    }

    public function test_print_repeats_receipt_information_and_splits_every_two_photos(): void
    {
        $document = $this->createDocument();
        $this->put(route('documents.update', $document), $this->data(['photos' => [
            ['file' => UploadedFile::fake()->image('ketiga.jpg'), 'caption' => '<script>alert(1)</script>'],
        ]]))->assertSessionHasNoErrors();
        $response = $this->get(route('documents.print', $document))->assertOk()
            ->assertSee('Kategori pembangunan')->assertSee('Tanggal nota')->assertSee('Nomor nota')
            ->assertSee('Halaman 1 dari 4')->assertSee('Halaman 4 dari 4')->assertSee('Gambar 3')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->assertSame(2, substr_count($response->getContent(), 'data-section="documentation"'));
        $this->assertSame(4, substr_count($response->getContent(), '<section class="sheet '));
        $this->assertSame(3, substr_count($response->getContent(), '<dd>NT/2026/001</dd>'));
    }

    public function test_search_category_filter_and_empty_result_work(): void
    {
        $this->createDocument();
        $this->post(route('documents.store'), $this->data(['category' => 'Pengecatan bangunan', 'receipt_number' => 'CAT-002']))->assertSessionHasNoErrors();
        $this->get(route('documents.index', ['q' => 'CAT-002']))->assertOk()->assertSee('CAT-002')->assertDontSee('NT/2026/001');
        $this->get(route('documents.index', ['category' => 'Renovasi atap']))->assertOk()->assertSee('NT/2026/001')->assertDontSee('CAT-002');
        $this->get(route('documents.index', ['q' => 'tidakada']))->assertOk()->assertSee('Nota belum ditemukan');
        $this->get(route('documents.create'))->assertOk()->assertSee('Tambah nota');
    }

    public function test_malformed_photo_removal_data_returns_validation_error(): void
    {
        $document = $this->createDocument();
        $this->put(route('documents.update', $document), $this->data([
            'photos' => [], 'remove_photos' => [['invalid']],
        ]))->assertSessionHasErrors('remove_photos.0');
    }
}
