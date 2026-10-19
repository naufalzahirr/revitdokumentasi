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
