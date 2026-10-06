<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signIn();
    }

    private function note(string $category): array
    {
        return ['category' => $category, 'receipt_date' => '2026-10-06', 'receipt_number' => 'NT-001'];
    }

    public function test_only_the_seven_official_categories_are_preloaded(): void
    {
        $expected = [
            'Pembangunan Baru - RPS Produksi dan Siaran Program Televisi',
            'Pembangunan Baru - RPS Pengembangan Gim',
            'Rehabilitasi Sedang - Ruang Kelas',
            'Rehabilitasi Sedang - Perpustakaan',
            'Pengecatan Ruang Lainnya',
            'Pengadaan Perabot - RPS Pengembangan Gim',
            'Pengadaan Perabot - RPS Produksi dan Siaran Program Televisi',
        ];

        $this->assertSame($expected, Category::orderBy('id')->pluck('name')->all());
        $response = $this->get(route('documents.create'))->assertOk();
        foreach ($expected as $name) {
            $response->assertSee($name);
            $this->post(route('documents.store'), $this->note($name))->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('documents', 7);
    }

    public function test_added_category_is_available_in_create_edit_and_filter_dropdowns(): void
    {
        $this->post(route('categories.store'), ['name' => '  Pembangunan laboratorium  '])
            ->assertRedirect(route('categories.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', ['name' => 'Pembangunan laboratorium']);
        $this->get(route('documents.create'))->assertOk()->assertSee('<select id="category" name="category" required>', false)
            ->assertSee('Pembangunan laboratorium')->assertDontSee('<datalist', false);
        $this->post(route('documents.store'), $this->note('Pembangunan laboratorium'))->assertSessionHasNoErrors();
        $document = Document::firstOrFail();
        $this->get(route('documents.edit', $document))->assertOk()->assertSee('value="Pembangunan laboratorium" selected', false);
        $this->get(route('documents.index'))->assertOk()->assertSee('Pembangunan laboratorium');
        $this->get(route('categories.index'))->assertOk()->assertSee('1 nota')->assertSee('Sedang digunakan');
    }

    public function test_duplicate_blank_and_oversized_category_names_are_rejected(): void
    {
        foreach (['Pembangunan Baru - RPS Produksi dan Siaran Program Televisi', '   ', str_repeat('a', 101)] as $name) {
            $this->post(route('categories.store'), ['name' => $name])->assertSessionHasErrors('name');
        }
        $this->assertDatabaseCount('categories', 7);
        $this->post(route('documents.store'), $this->note('Kategori tidak tersedia'))->assertSessionHasErrors('category');
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_rename_updates_existing_notes_without_changing_voucher_numbers(): void
    {
        $this->post(route('documents.store'), $this->note('Pembangunan Baru - RPS Produksi dan Siaran Program Televisi'))->assertSessionHasNoErrors();
        $document = Document::firstOrFail();
        $category = Category::where('name', 'Pembangunan Baru - RPS Produksi dan Siaran Program Televisi')->firstOrFail();
        $this->get(route('categories.edit', $category))->assertOk();
        $this->put(route('categories.update', $category), ['name' => 'Pembangunan Baru - RPS Produksi dan Siaran Program Televisi'])->assertSessionHasNoErrors();
        $this->put(route('categories.update', $category), ['name' => 'Rehabilitasi Sedang - Ruang Kelas'])->assertSessionHasErrors('name');
        $this->put(route('categories.update', $category), ['name' => 'Perbaikan atap'])
            ->assertRedirect(route('categories.index'))->assertSessionHasNoErrors();
        $this->assertSame('Perbaikan atap', $document->fresh()->category);
        $this->assertSame('001/REV.SMK', $document->fresh()->voucher_number);
        $this->get(route('documents.print', $document))->assertOk()->assertSee('Perbaikan atap');
    }

    public function test_only_unused_categories_can_be_deleted(): void
    {
        $this->post(route('documents.store'), $this->note('Pembangunan Baru - RPS Produksi dan Siaran Program Televisi'))->assertSessionHasNoErrors();
        $used = Category::where('name', 'Pembangunan Baru - RPS Produksi dan Siaran Program Televisi')->firstOrFail();
        $unused = Category::where('name', 'Rehabilitasi Sedang - Ruang Kelas')->firstOrFail();
        $this->delete(route('categories.destroy', $used))->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $used->id]);
        $this->delete(route('categories.destroy', $unused))->assertRedirect(route('categories.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('categories', ['id' => $unused->id]);
        $this->post(route('documents.store'), $this->note('Rehabilitasi Sedang - Ruang Kelas'))->assertSessionHasErrors('category');
    }

    public function test_migration_preserves_categories_already_used_in_legacy_notes(): void
    {
        $document = Document::create($this->note('Kategori arsip lama'));
        $migration = require database_path('migrations/2026_10_06_000006_create_categories_table.php');
        $migration->down();
        $migration->up();
        $this->assertDatabaseHas('categories', ['name' => 'Kategori arsip lama']);
        $this->assertSame('Kategori arsip lama', $document->fresh()->category);
        $this->get(route('documents.edit', $document))->assertOk()->assertSee('value="Kategori arsip lama" selected', false);
    }
}
