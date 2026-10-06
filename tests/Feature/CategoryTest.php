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
        foreach (['Renovasi atap', '   ', str_repeat('a', 101)] as $name) {
            $this->post(route('categories.store'), ['name' => $name])->assertSessionHasErrors('name');
        }
        $this->assertDatabaseCount('categories', 6);
        $this->post(route('documents.store'), $this->note('Kategori tidak tersedia'))->assertSessionHasErrors('category');
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_rename_updates_existing_notes_without_changing_voucher_numbers(): void
    {
        $this->post(route('documents.store'), $this->note('Renovasi atap'))->assertSessionHasNoErrors();
        $document = Document::firstOrFail();
        $category = Category::where('name', 'Renovasi atap')->firstOrFail();
        $this->get(route('categories.edit', $category))->assertOk();
        $this->put(route('categories.update', $category), ['name' => 'Renovasi atap'])->assertSessionHasNoErrors();
        $this->put(route('categories.update', $category), ['name' => 'Pembangunan pagar'])->assertSessionHasErrors('name');
        $this->put(route('categories.update', $category), ['name' => 'Perbaikan atap'])
            ->assertRedirect(route('categories.index'))->assertSessionHasNoErrors();
        $this->assertSame('Perbaikan atap', $document->fresh()->category);
        $this->assertSame('001/REV.SMK', $document->fresh()->voucher_number);
        $this->get(route('documents.print', $document))->assertOk()->assertSee('Perbaikan atap');
    }

    public function test_only_unused_categories_can_be_deleted(): void
    {
        $this->post(route('documents.store'), $this->note('Renovasi atap'))->assertSessionHasNoErrors();
        $used = Category::where('name', 'Renovasi atap')->firstOrFail();
        $unused = Category::where('name', 'Pembangunan pagar')->firstOrFail();
        $this->delete(route('categories.destroy', $used))->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $used->id]);
        $this->delete(route('categories.destroy', $unused))->assertRedirect(route('categories.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('categories', ['id' => $unused->id]);
        $this->post(route('documents.store'), $this->note('Pembangunan pagar'))->assertSessionHasErrors('category');
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
