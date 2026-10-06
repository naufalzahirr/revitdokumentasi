<?php

namespace Tests\Feature;

use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentItemTest extends TestCase
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
        return array_merge(['category' => 'Pembangunan Baru - RPS Produksi dan Siaran Program Televisi', 'receipt_date' => '2026-10-06', 'receipt_number' => 'ITEM-001'], $extra);
    }

    private function photo(string $caption = 'Foto item.'): array
    {
        return ['file' => UploadedFile::fake()->image('foto.jpg'), 'caption' => $caption];
    }

    private function createNote(): Document
    {
        $this->post(route('documents.store'), $this->data(['items' => [
            ['name' => 'Semen', 'photos' => [$this->photo('Karung semen pertama.'), $this->photo('Karung semen kedua.')]],
            ['name' => 'Pasir', 'photos' => [$this->photo('Pasir yang diterima.')]],
            ['name' => 'Rangka atap'],
        ]]))->assertSessionHasNoErrors();

        return Document::latest('id')->firstOrFail()->load('items.photos', 'photos');
    }

    public function test_multiple_items_with_multiple_photos_and_empty_items_are_saved_and_grouped(): void
    {
        $document = $this->createNote();
        $this->assertCount(3, $document->items);
        $this->assertCount(2, $document->items[0]->photos);
        $this->assertCount(1, $document->items[1]->photos);
        $this->assertCount(0, $document->items[2]->photos);
        foreach ($document->photos as $photo) {
            $this->assertSame($document->id, $photo->item->document_id);
            Storage::disk('local')->assertExists($photo->path);
        }
        $this->get(route('documents.show', $document))->assertOk()
            ->assertSeeInOrder(['Semen', 'Karung semen pertama.', 'Karung semen kedua.', 'Pasir', 'Pasir yang diterima.', 'Rangka atap', 'Foto item bisa menyusul.']);
        $this->get(route('documents.edit', $document))->assertOk()->assertSee('Tambah item')->assertSee('name="items[item_0][name]"', false)
            ->assertSee('name="items[item_0][existing]['.$document->items[0]->photos[0]->id.'][caption]"', false);
    }

    public function test_empty_item_can_be_completed_and_other_items_kept_without_resubmitting_them(): void
    {
        $document = $this->createNote();
        $empty = $document->items[2];
        $this->put(route('documents.update', $document), $this->data(['items' => [
            ['id' => $empty->id, 'name' => 'Rangka baja ringan', 'photos' => [$this->photo('Rangka dipasang.')]],
            ['name' => 'Baut'],
        ]]))->assertSessionHasNoErrors();
        $this->assertCount(4, $document->fresh()->items);
        $this->assertCount(4, $document->fresh()->photos);
        $this->assertSame('Rangka baja ringan', $empty->fresh()->name);
        $this->assertSame('Rangka dipasang.', $empty->fresh()->photos->first()->caption);
        $this->assertSame('001/REV.SMK', $document->fresh()->voucher_number);
    }

    public function test_editing_and_deleting_photos_and_items_cleans_up_only_their_files(): void
    {
        $document = $this->createNote();
        [$semen, $pasir] = $document->items;
        [$keep, $remove] = $semen->photos;
        $this->put(route('documents.update', $document), $this->data(['items' => [
            ['id' => $semen->id, 'name' => 'Semen Portland', 'existing' => [$keep->id => ['caption' => 'Keterangan diperbarui.']],
                'remove_photos' => [$remove->id], 'photos' => [$this->photo('Foto baru.')]],
        ], 'remove_items' => [$pasir->id]]))->assertSessionHasNoErrors();
        $this->assertCount(2, $document->fresh()->items);
        $this->assertCount(2, $semen->fresh()->photos);
        $this->assertSame('Keterangan diperbarui.', $keep->fresh()->caption);
        Storage::disk('local')->assertExists($keep->path);
        Storage::disk('local')->assertMissing($remove->path);
        Storage::disk('local')->assertMissing($pasir->photos[0]->path);
        $this->assertDatabaseMissing('document_items', ['id' => $pasir->id]);
        $this->assertDatabaseMissing('document_photos', ['id' => $pasir->photos[0]->id]);
    }

    public function test_item_and_photo_ownership_is_checked_even_between_items_of_the_same_note(): void
    {
        $first = $this->createNote();
        $second = $this->createNote();
        $ownItem = $first->items[0];
        $otherItemPhoto = $first->items[1]->photos[0];
        $foreignItem = $second->items[0];
        $this->put(route('documents.update', $first), $this->data(['items' => [
            ['id' => $foreignItem->id, 'name' => 'Dilarang'],
        ]]))->assertSessionHasErrors('items.0.id');
        $this->put(route('documents.update', $first), $this->data(['remove_items' => [$foreignItem->id]]))->assertSessionHasErrors('remove_items.0');
        foreach ([['existing' => [$otherItemPhoto->id => ['caption' => 'Dilarang']]], ['remove_photos' => [$otherItemPhoto->id]]] as $change) {
            $this->put(route('documents.update', $first), $this->data(['items' => [
                array_merge(['id' => $ownItem->id, 'name' => 'Semen'], $change),
            ]]))->assertSessionHasErrors('items.0.existing');
        }
        $this->put(route('documents.update', $first), $this->data(['items' => [
            ['name' => 'Item baru', 'existing' => [$ownItem->photos[0]->id => ['caption' => 'Dilarang']]],
        ]]))->assertSessionHasErrors('items.0.existing');
        $this->assertSame('Pasir yang diterima.', $otherItemPhoto->fresh()->caption);
        Storage::disk('local')->assertExists($otherItemPhoto->path);
        $this->assertCount(3, $first->fresh()->items);
    }

    public function test_invalid_empty_names_duplicate_ids_and_malformed_nested_data_are_rejected(): void
    {
        $document = $this->createNote();
        $item = $document->items[0];
        $cases = [
            ['items' => [['name' => ' ']]],
            ['items' => [['name' => str_repeat('x', 201)]]],
            ['items' => [['id' => $item->id, 'name' => 'A'], ['id' => $item->id, 'name' => 'B']]],
            ['items' => [['id' => $item->id, 'name' => 'A']], 'remove_items' => [$item->id]],
            ['items' => [['name' => 'A', 'existing' => 'malformed']]],
            ['items' => [['name' => 'A', 'remove_photos' => 'malformed']]],
            ['items' => 'malformed'],
            ['items' => [['name' => 'A', 'photos' => [['file' => UploadedFile::fake()->create('file.pdf', 1)]]]]],
            ['items' => [['name' => 'A', 'photos' => [['file' => UploadedFile::fake()->image('large.jpg')->size(5121)]]]]],
            ['items' => [['name' => 'A', 'photos' => [$this->photo(str_repeat('x', 501))]]]],
        ];
        foreach ($cases as $case) {
            $this->put(route('documents.update', $document), $this->data($case))->assertSessionHasErrors();
        }
        $this->assertCount(3, $document->fresh()->items);
        $this->assertCount(3, $document->fresh()->photos);
    }

    public function test_total_photo_limit_counts_all_items_and_allows_removing_an_item_to_make_room(): void
    {
        $document = $this->createNote();
        $newPhotos = array_map(fn () => $this->photo(), range(1, 18));
        $this->put(route('documents.update', $document), $this->data(['items' => [
            ['name' => 'Baut', 'photos' => $newPhotos],
        ]]))->assertSessionHasErrors('photos');
        $this->assertCount(3, $document->fresh()->photos);
        $this->put(route('documents.update', $document), $this->data(['remove_items' => [$document->items[0]->id], 'items' => [
            ['name' => 'Baut', 'photos' => $newPhotos],
        ]]))->assertSessionHasNoErrors();
        $this->assertCount(19, $document->fresh()->photos);
    }

    public function test_cleared_caption_is_preserved_when_another_field_fails_validation(): void
    {
        $document = $this->createNote();
        $item = $document->items[0];
        $photo = $item->photos[0];
        $editUrl = route('documents.edit', $document);
        $this->from($editUrl)->put(route('documents.update', $document), $this->data([
            'amount' => 9999999999999,
            'items' => [['id' => $item->id, 'name' => 'Semen Portland', 'existing' => [$photo->id => ['caption' => '']]]],
        ]))->assertRedirect($editUrl)->assertSessionHasErrors('amount');

        $response = $this->get($editUrl)->assertOk()->assertSee('value="Semen Portland"', false);
        $html = new \DOMDocument;
        @$html->loadHTML($response->getContent());
        $xpath = new \DOMXPath($html);
        $caption = $xpath->query('//textarea[@id="caption-item_0-'.$photo->id.'"]')->item(0);
        $this->assertSame('', $caption->textContent);
        $this->assertSame('Karung semen pertama.', $photo->fresh()->caption);
    }

    public function test_total_item_limit_includes_items_kept_from_previous_edits(): void
    {
        $document = $this->createNote();
        foreach (range(1, 47) as $number) {
            $document->items()->create(['name' => 'Item '.$number]);
        }
        $this->put(route('documents.update', $document), $this->data(['items' => [['name' => 'Item berlebih']]]))->assertSessionHasErrors('items');
        $this->put(route('documents.update', $document), $this->data(['remove_items' => [$document->items[2]->id], 'items' => [['name' => 'Item pengganti']]]))->assertSessionHasNoErrors();
        $this->assertCount(50, $document->fresh()->items);
    }

    public function test_print_and_shared_pages_include_item_names_empty_items_and_two_photos_per_page(): void
    {
        $document = $this->createNote();
        $response = $this->get(route('documents.print', $document))->assertOk()
            ->assertSee('Semen')->assertSee('Pasir')->assertSee('Rangka atap')->assertSee('Foto item belum ditambahkan.')
            ->assertSee('Gambar 3')->assertSee('Halaman 4 dari 4');
        $html = new \DOMDocument;
        @$html->loadHTML($response->getContent());
        $xpath = new \DOMXPath($html);
        $sheets = $xpath->query('//section[@data-section="documentation"]');
        $this->assertCount(2, $sheets);
        foreach ($sheets as $sheet) {
            $this->assertLessThanOrEqual(2, $xpath->query('.//figure', $sheet)->length);
        }
        $this->post(route('documents.share', $document));
        $token = $document->fresh()->share_token;
        $this->get(route('shared.show', $token))->assertOk()->assertSee('Semen')->assertSee('Rangka atap')->assertSee('Karung semen pertama.');
        $this->get(route('shared.print', $token))->assertOk()->assertSee('Foto item belum ditambahkan.')
            ->assertSee(route('shared.photo', ['document' => $token, 'photo' => $document->photos[0]]));
    }

    public function test_delete_note_removes_items_and_files_and_all_items_can_be_removed_without_deleting_note(): void
    {
        $document = $this->createNote();
        $this->put(route('documents.update', $document), $this->data(['items_present' => 1, 'remove_items' => $document->items->pluck('id')->all()]))->assertSessionHasNoErrors();
        $this->assertCount(0, $document->fresh()->items);
        $this->assertCount(0, $document->fresh()->photos);
        foreach ($document->photos as $photo) {
            Storage::disk('local')->assertMissing($photo->path);
        }
        $this->assertDatabaseCount('documents', 1);
        $other = $this->createNote();
        $this->delete(route('documents.destroy', $other))->assertRedirect();
        $this->assertDatabaseCount('document_items', 0);
        $this->assertDatabaseCount('document_photos', 0);
        foreach ($other->photos as $photo) {
            Storage::disk('local')->assertMissing($photo->path);
        }
    }

    public function test_item_migration_preserves_legacy_photo_paths_and_captions(): void
    {
        $document = $this->createNote();
        $photos = $document->photos->map(fn ($photo) => $photo->only('id', 'path', 'caption'))->all();
        $migration = require database_path('migrations/2026_10_06_000008_add_document_items.php');
        $migration->down();
        $migration->up();
        $this->assertCount(1, $document->fresh()->items);
        $this->assertSame('Dokumentasi sebelumnya', $document->fresh()->items->first()->name);
        $this->assertSame($photos, $document->fresh()->photos->map(fn ($photo) => $photo->only('id', 'path', 'caption'))->all());
        $this->assertCount(3, $document->fresh()->items->first()->photos);
    }
}
