<?php

namespace Tests\Feature;

use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandoverReportTest extends TestCase
{
    use RefreshDatabase;

    private function createDocument(): Document
    {
        $this->signIn();

        return Document::create([
            'category' => 'Pembangunan Baru - RPS Pengembangan Gim',
            'receipt_date' => '2026-10-19', 'receipt_number' => 'NOTA-279',
            'recipient_name' => 'Penerima pembayaran bukan data penyedia',
        ]);
    }

    private function xpath(string $content): \DOMXPath
    {
        $html = new \DOMDocument;
        @$html->loadHTML($content);

        return new \DOMXPath($html);
    }

    public function test_handover_form_saves_quantities_and_details_and_prints_them_in_internal_and_shared_reports(): void
    {
        $this->signIn();
        $data = [
            'category' => 'Pembangunan Baru - RPS Pengembangan Gim',
            'receipt_date' => '2026-10-19', 'receipt_number' => 'NOTA-BAST',
            'handover_number' => '015', 'handover_date' => '2027-01-04',
            'order_number' => '010', 'order_date' => '2026-12-29',
            'supplier_name' => 'Penyedia <b>Material</b>',
            'items' => [
                ['name' => 'Kawat ikat', 'ordered_quantity' => '2.5', 'received_good_quantity' => '2.5', 'received_damaged_quantity' => '0'],
                ['name' => 'Semen', 'ordered_quantity' => '10', 'received_good_quantity' => '9', 'received_damaged_quantity' => '1'],
            ],
        ];
        $this->get(route('documents.create'))->assertOk()->assertSee('Berita Acara Serah Terima')
            ->assertSee('Jumlah diterima kondisi rusak');
        $this->post(route('documents.store'), $data)->assertSessionHasNoErrors();
        $document = Document::latest('id')->firstOrFail();
        $this->assertSame('2027-01-04', $document->handover_date->format('Y-m-d'));
        $this->assertSame('2026-12-29', $document->order_date->format('Y-m-d'));
        $this->assertSame('015', $document->handover_number);
        $this->assertSame('010', $document->order_number);
        $this->assertSame($data['supplier_name'], $document->supplier_name);
        $this->assertSame(2.5, $document->items[0]->ordered_quantity);
        $this->assertSame(0.0, $document->items[0]->received_damaged_quantity);

        $response = $this->get(route('documents.edit', $document))->assertOk();
        $xpath = $this->xpath($response->getContent());
        foreach (['ordered_quantity' => '2.5', 'received_good_quantity' => '2.5', 'received_damaged_quantity' => '0'] as $field => $value) {
            $this->assertSame($value, $xpath->query('//input[@name="items[item_0]['.$field.']"]')->item(0)->getAttribute('value'));
        }
        $this->post(route('documents.share', $document))->assertRedirect();
        foreach ([route('documents.print', $document), route('shared.print', $document->fresh()->share_token)] as $url) {
            $response = $this->get($url)->assertOk();
            $xpath = $this->xpath($response->getContent());
            $sheet = $xpath->query('//section[@data-section="handover"]')->item(0);
            foreach (['015/BAST-BOSP/SMKN4/2027', '010/SP-BOSP/SMKN4/2026', 'Senin', '04 Januari 2027', '29 Desember 2026', $data['supplier_name']] as $text) {
                $this->assertStringContainsString($text, $sheet->textContent);
            }
            $this->assertCount(0, $xpath->query('.//b', $sheet));
            foreach ([['2.5', '2.5', '0'], ['10', '9', '1']] as $index => $values) {
                $row = $xpath->query('.//tbody/tr', $sheet)->item($index);
                foreach ($values as $column => $value) {
                    $this->assertSame($value, $xpath->query('./td['.($column + 3).']', $row)->item(0)->textContent);
                }
            }
        }
    }

    public function test_handover_values_can_be_updated_cleared_and_preserved_when_omitted(): void
    {
        $document = $this->createDocument();
        $item = $document->items()->create(['name' => 'Kawat ikat', 'ordered_quantity' => 2, 'received_good_quantity' => 2, 'received_damaged_quantity' => 0]);
        $data = $document->only(['category', 'receipt_number']);
        $data['receipt_date'] = '2026-10-19';
        $this->put(route('documents.update', $document), array_merge($data, [
            'handover_number' => '001', 'handover_date' => '2026-10-23', 'supplier_name' => 'Penyedia',
            'items' => [['id' => $item->id, 'name' => 'Kawat ikat 2 roll', 'received_good_quantity' => '1.5', 'received_damaged_quantity' => '0.5']],
        ]))->assertSessionHasNoErrors();
        $this->assertSame(2.0, $item->fresh()->ordered_quantity);
        $this->assertSame(1.5, $item->fresh()->received_good_quantity);
        $this->assertSame(0.5, $item->fresh()->received_damaged_quantity);
        $this->put(route('documents.update', $document), $data)->assertSessionHasNoErrors();
        $this->assertSame('001', $document->fresh()->handover_number);
        $this->assertSame(1.5, $item->fresh()->received_good_quantity);
        $this->put(route('documents.update', $document), array_merge($data, [
            'handover_number' => '', 'handover_date' => '', 'supplier_name' => '',
            'items' => [['id' => $item->id, 'name' => $item->fresh()->name, 'ordered_quantity' => '', 'received_good_quantity' => '', 'received_damaged_quantity' => '']],
        ]))->assertSessionHasNoErrors();
        $this->assertNull($document->fresh()->handover_number);
        $this->assertNull($document->fresh()->handover_date);
        $this->assertNull($document->fresh()->supplier_name);
        $this->assertNull($item->fresh()->ordered_quantity);
        $this->assertNull($item->fresh()->received_good_quantity);
        $this->assertNull($item->fresh()->received_damaged_quantity);
    }

    public function test_invalid_handover_values_are_rejected_and_form_retains_submitted_quantities(): void
    {
        $document = $this->createDocument();
        $data = ['category' => $document->category, 'receipt_number' => $document->receipt_number, 'receipt_date' => '2026-10-19'];
        foreach (['ordered_quantity', 'received_good_quantity', 'received_damaged_quantity'] as $field) {
            foreach (['-1', 'abc', '1000000000', '1.2345', ['invalid']] as $invalid) {
                $this->put(route('documents.update', $document), array_merge($data, ['items' => [['name' => 'Kawat', $field => $invalid]]]))
                    ->assertSessionHasErrors('items.0.'.$field);
            }
        }
        $editUrl = route('documents.edit', $document);
        $this->from($editUrl)->put(route('documents.update', $document), array_merge($data, [
            'handover_date' => '2026-02-30', 'order_date' => 'invalid',
            'items' => [['name' => 'Kawat', 'ordered_quantity' => '3', 'received_good_quantity' => '2', 'received_damaged_quantity' => '0']],
        ]))->assertRedirect($editUrl)->assertSessionHasErrors(['handover_date', 'order_date']);
        $xpath = $this->xpath($this->get($editUrl)->assertOk()->getContent());
        foreach (['ordered_quantity' => '3', 'received_good_quantity' => '2', 'received_damaged_quantity' => '0'] as $field => $value) {
            $this->assertSame($value, $xpath->query('//input[@name="items[item_0]['.$field.']"]')->item(0)->getAttribute('value'));
        }
        $this->assertDatabaseCount('document_items', 0);
    }

    public function test_internal_and_shared_reports_use_note_items_and_leave_unknown_data_and_number_prefixes_empty(): void
    {
        $document = $this->createDocument();
        $document->items()->create(['name' => 'Semen']);
        $document->items()->create(['name' => '<script>alert(1)</script>']);
        $this->post(route('documents.share', $document))->assertRedirect();
        $token = $document->fresh()->share_token;

        foreach ([route('documents.print', $document), route('shared.print', $token)] as $url) {
            $response = $this->get($url)->assertOk()->assertSee('Halaman 4 dari 4');
            $xpath = $this->xpath($response->getContent());
            $sheet = $xpath->query('//section[@data-section="handover"]')->item(0);
            $this->assertNotNull($sheet);
            $this->assertStringContainsString('Yayuk Sri Mulyani Rahayu', $sheet->textContent);
            $this->assertStringContainsString('/BAST-BOSP/SMKN4/2026', $sheet->textContent);
            $this->assertStringContainsString('/SP-BOSP/SMKN4/2026', $sheet->textContent);
            $this->assertStringNotContainsString('279/BAST', $sheet->textContent);
            $this->assertStringNotContainsString('278/SP', $sheet->textContent);
            $this->assertStringNotContainsString('Sanford', $sheet->textContent);
            $this->assertStringNotContainsString('Pinang Sentosa', $sheet->textContent);
            $this->assertStringNotContainsString('Penerima pembayaran bukan data penyedia', $sheet->textContent);
            $this->assertStringNotContainsString('19 Oktober', $sheet->textContent);
            $rows = $xpath->query('.//tbody/tr', $sheet);
            $this->assertCount(3, $rows);
            $this->assertSame('Semen', $xpath->query('./td[2]', $rows[0])->item(0)->textContent);
            $this->assertSame('<script>alert(1)</script>', $xpath->query('./td[2]', $rows[1])->item(0)->textContent);
            $this->assertCount(0, $xpath->query('.//script', $sheet));
            foreach ($xpath->query('.//tbody/tr/td[position() >= 3] | .//span[@aria-hidden="true"]', $sheet) as $blank) {
                $this->assertSame('', trim($blank->textContent));
            }
            $this->assertCount(2, $xpath->query('.//span[@class="handover-manual-number"]', $sheet));
            foreach ($xpath->query('.//header//img/@src', $sheet) as $image) {
                $this->assertFileExists(public_path(ltrim(parse_url($image->value, PHP_URL_PATH), '/')));
            }
        }
    }

    public function test_every_note_includes_a_blank_handover_even_without_items(): void
    {
        $response = $this->get(route('documents.print', $this->createDocument()))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $this->assertCount(1, $xpath->query('//section[@data-section="handover"]'));
        $this->assertCount(3, $xpath->query('//section[@data-section="handover"]//tbody/tr'));
        foreach ($xpath->query('//section[@data-section="handover"]//tbody/tr/td[position() > 1]') as $blank) {
            $this->assertSame('', trim($blank->textContent));
        }
    }

    public function test_many_long_items_continue_in_order_with_correct_page_numbers(): void
    {
        $document = $this->createDocument();
        $names = [];
        foreach (range(1, 50) as $number) {
            $names[] = str_pad('Barang '.$number.' ', 200, 'material ');
            $document->items()->create(['name' => end($names)]);
        }
        $response = $this->get(route('documents.print', $document))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $sheets = $xpath->query('//section');
        $handover = $xpath->query('//section[@data-section="handover"]');
        $this->assertGreaterThan(1, $handover->length);
        $actualNames = [];
        $numbers = [];
        foreach ($handover as $sheet) {
            foreach ($xpath->query('.//tbody/tr[td[2]/text()]', $sheet) as $row) {
                $numbers[] = (int) $xpath->query('./td[1]', $row)->item(0)->textContent;
                $actualNames[] = $xpath->query('./td[2]', $row)->item(0)->textContent;
            }
        }
        $this->assertSame($names, $actualNames);
        $this->assertSame(range(1, 50), $numbers);
        foreach ($sheets as $index => $sheet) {
            $footer = $xpath->query('.//footer/span[2]', $sheet)->item(0);
            $this->assertSame('Halaman '.($index + 1).' dari '.$sheets->length, $footer->textContent);
        }
    }
}
