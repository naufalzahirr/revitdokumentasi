<?php

namespace Tests\Feature;

use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptSharingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->signIn();
    }

    private function data(array $extra = []): array
    {
        return array_merge(['category' => 'Pembangunan Baru - RPS Produksi dan Siaran Program Televisi', 'receipt_date' => '2026-10-06', 'receipt_number' => 'NT-001'], $extra);
    }

    private function createDocument(array $extra = []): Document
    {
        $this->post(route('documents.store'), $this->data($extra))->assertSessionHasNoErrors();

        return Document::latest('id')->firstOrFail();
    }

    public function test_receipt_image_can_be_uploaded_replaced_preserved_and_removed(): void
    {
        $document = $this->createDocument(['receipt_image' => UploadedFile::fake()->image('nota.jpg')]);
        $firstPath = $document->receipt_image_path;
        Storage::disk('local')->assertExists($firstPath);
        $this->get(route('documents.receipt-image', $document))->assertOk();
        $this->get(route('documents.show', $document))->assertOk()->assertSee('Gambar nota');
        $this->put(route('documents.update', $document), $this->data())->assertSessionHasNoErrors();
        $this->assertSame($firstPath, $document->fresh()->receipt_image_path);
        $this->put(route('documents.update', $document), $this->data([
            'receipt_image' => UploadedFile::fake()->image('pengganti.png'), 'remove_receipt_image' => 1,
        ]))->assertSessionHasNoErrors();
        $secondPath = $document->fresh()->receipt_image_path;
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($secondPath);
        $this->put(route('documents.update', $document), $this->data(['remove_receipt_image' => 1]))->assertSessionHasNoErrors();
        $this->assertNull($document->fresh()->receipt_image_path);
        Storage::disk('local')->assertMissing($secondPath);
        $this->get(route('documents.receipt-image', $document))->assertNotFound();
    }

    public function test_invalid_receipt_images_are_rejected_and_delete_cleans_up_image(): void
    {
        $this->post(route('documents.store'), $this->data(['receipt_image' => UploadedFile::fake()->create('nota.pdf', 10)]))->assertSessionHasErrors('receipt_image');
        $this->post(route('documents.store'), $this->data(['receipt_image' => UploadedFile::fake()->image('besar.jpg')->size(5121)]))->assertSessionHasErrors('receipt_image');
        $document = $this->createDocument(['receipt_image' => UploadedFile::fake()->image('nota.jpg')]);
        $path = $document->receipt_image_path;
        $this->delete(route('documents.destroy', $document))->assertRedirect();
        Storage::disk('local')->assertMissing($path);
    }

    public function test_combined_print_includes_receipt_as_a_separate_page(): void
    {
        $document = $this->createDocument([
            'receipt_image' => UploadedFile::fake()->image('nota.jpg'),
            'photos' => [['file' => UploadedFile::fake()->image('kegiatan.jpg'), 'caption' => 'Atap baru.']],
        ]);
        $response = $this->get(route('documents.print', $document))->assertOk()->assertSee('Foto Nota')
            ->assertSee('Halaman 1 dari 3')->assertSee('Halaman 2 dari 3')->assertSee('Halaman 3 dari 3')
            ->assertSeeInOrder(['BUKTI PENGELUARAN DANA', '<h1>Foto Nota</h1>', '<h1>Dokumentasi Revitalisasi</h1>'], false);
        $this->assertSame(1, substr_count($response->getContent(), '<h1>Dokumentasi Revitalisasi</h1>'));
    }

    public function test_share_link_is_stable_read_only_and_revocable_for_all_shared_routes(): void
    {
        $document = $this->createDocument([
            'receipt_image' => UploadedFile::fake()->image('nota.jpg'),
            'photos' => [['file' => UploadedFile::fake()->image('foto.jpg'), 'caption' => 'Foto kegiatan.']],
        ]);
        $this->post(route('documents.share', $document))->assertRedirect(route('documents.show', $document));
        $token = $document->fresh()->share_token;
        $this->assertSame(48, strlen($token));
        $this->post(route('documents.share', $document))->assertRedirect();
        $this->assertSame($token, $document->fresh()->share_token);
        $photo = $document->photos->first();
        $urls = [route('shared.show', $token), route('shared.print', $token), route('shared.voucher', $token),
            route('shared.receipt-image', $token), route('shared.photo', ['document' => $token, 'photo' => $photo])];
        $this->get($urls[0])->assertOk()->assertSee('NT-001')->assertSee('Foto kegiatan.')
            ->assertDontSee('Edit nota')->assertDontSee('Hapus nota')->assertDontSee('Buat tautan berbagi')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        foreach ($urls as $url) {
            $this->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        }
        $this->put($urls[0], $this->data(['category' => 'Dilarang berubah']))->assertStatus(405);
        $this->assertSame('Pembangunan Baru - RPS Produksi dan Siaran Program Televisi', $document->fresh()->category);
        $this->delete(route('documents.unshare', $document))->assertRedirect();
        foreach ($urls as $url) {
            $this->get($url)->assertNotFound();
        }
        $this->post(route('documents.share', $document))->assertRedirect();
        $this->assertNotSame($token, $document->fresh()->share_token);
    }

    public function test_shared_token_cannot_access_other_receipts_photos_or_nonexistent_tokens(): void
    {
        $first = $this->createDocument();
        $second = $this->createDocument(['photos' => [['file' => UploadedFile::fake()->image('foto.jpg')]]]);
        $this->post(route('documents.share', $first))->assertRedirect();
        $this->get(route('shared.photo', ['document' => $first->fresh()->share_token, 'photo' => $second->photos->first()]))->assertNotFound();
        $this->get(route('shared.show', str_repeat('a', 48)))->assertNotFound();
        $this->get(route('shared.show', $first->id))->assertNotFound();
    }

    public function test_payment_details_are_saved_edited_and_printed_with_automatic_words(): void
    {
        $document = $this->createDocument([
            'receipt_date' => '2026-07-23', 'amount' => 6150328,
            'purpose' => 'Perjalanan dinas revitalisasi SMK', 'recipient_name' => 'Penerima Pembayaran',
        ]);
        $this->get(route('documents.voucher', $document))->assertOk()->assertSee('BUKTI PENGELUARAN DANA')
            ->assertSee('001/REV.SMK')->assertSee('Rp. 6.150.328')->assertSee('Enam juta seratus lima puluh ribu tiga ratus dua puluh delapan rupiah')
            ->assertSee('23 Juli 2026')->assertSee('197704212005022011')->assertSee('Yayuk Sri Mulyani Rahayu')
            ->assertSee('Riri Yulianti Solfia')->assertSee('Perjalanan dinas revitalisasi SMK');
        $this->get(route('documents.edit', $document))->assertOk()->assertSee('6150328')->assertSee('199107262019032001');
        $this->put(route('documents.update', $document), $this->data(['amount' => 1000]))->assertSessionHasNoErrors();
        $this->get(route('documents.voucher', $document))->assertOk()->assertSee('Rp. 1.000')->assertSee('Seribu rupiah');
        $this->post(route('documents.store'), $this->data(['amount' => -1]))->assertSessionHasErrors('amount');
        $this->post(route('documents.store'), $this->data(['amount' => '1.000']))->assertSessionHasErrors('amount');
    }
}
