<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Support\VoucherNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AutomaticVoucherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signIn();
    }

    private function data(array $extra = []): array
    {
        return array_merge(['category' => 'Pembangunan Baru - RPS Produksi dan Siaran Program Televisi', 'receipt_date' => '2026-10-06', 'receipt_number' => 'NT-001'], $extra);
    }

    private function createDocument(): Document
    {
        $this->post(route('documents.store'), $this->data())->assertSessionHasNoErrors();

        return Document::latest('id')->firstOrFail();
    }

    public function test_voucher_numbers_reuse_the_first_available_number_after_deletion(): void
    {
        $this->assertSame('001/REV.SMK', VoucherNumber::preview());
        $first = $this->createDocument();
        $second = $this->createDocument();
        $this->assertSame('001/REV.SMK', $first->voucher_number);
        $this->assertSame('002/REV.SMK', $second->voucher_number);
        $this->delete(route('documents.destroy', $first))->assertRedirect();
        $this->assertSame('001/REV.SMK', VoucherNumber::preview());
        $this->get(route('documents.create'))->assertOk()->assertSee('value="/REV.SMK"', false)->assertDontSee('001/REV.SMK');
        $third = $this->createDocument();
        $this->assertSame('001/REV.SMK', $third->voucher_number);
        $this->assertSame('002/REV.SMK', $second->fresh()->voucher_number);
        $this->assertSame('003/REV.SMK', VoucherNumber::preview());
        $this->assertSame('1000/REV.SMK', VoucherNumber::format(1000));
    }

    public function test_gaps_are_filled_in_order_without_renumbering_existing_notes(): void
    {
        $notes = array_map(fn () => $this->createDocument(), range(1, 5));
        foreach ([$notes[3], $notes[1]] as $note) {
            $this->delete(route('documents.destroy', $note))->assertRedirect();
        }
        $this->assertSame('002/REV.SMK', VoucherNumber::preview());
        $this->assertSame('002/REV.SMK', $this->createDocument()->voucher_number);
        $this->assertSame('004/REV.SMK', VoucherNumber::preview());
        $this->assertSame('004/REV.SMK', $this->createDocument()->voucher_number);
        $this->assertSame('006/REV.SMK', $this->createDocument()->voucher_number);
        foreach ([0, 2, 4] as $index) {
            $this->assertSame(VoucherNumber::format($index + 1), $notes[$index]->fresh()->voucher_number);
        }
    }

    public function test_empty_archive_starts_at_one_even_when_the_old_counter_is_high(): void
    {
        $note = $this->createDocument();
        $this->delete(route('documents.destroy', $note))->assertRedirect();
        DB::table('voucher_counters')->where('name', 'expenditure')->update(['last_number' => 1000]);
        $this->assertSame('001/REV.SMK', VoucherNumber::preview());
        $this->assertSame('001/REV.SMK', $this->createDocument()->voucher_number);
        $this->assertSame('002/REV.SMK', $this->createDocument()->voucher_number);
    }

    public function test_school_details_are_automatic_and_cannot_be_overridden_by_form_input(): void
    {
        $extra = ['voucher_number' => '999/FAKE', 'payment_date' => '2000-01-01', 'recipient_name' => 'Penerima yang diinput'];
        foreach (array_keys(config('voucher.fixed_fields')) as $field) {
            $extra[$field] = 'Data yang tidak boleh mengganti nilai otomatis';
        }
        $this->post(route('documents.store'), $this->data($extra))->assertSessionHasNoErrors();
        $document = Document::firstOrFail();
        $this->assertSame('001/REV.SMK', $document->voucher_number);
        foreach (config('voucher.fixed_fields') as $field => $value) {
            $this->assertSame($value, $document->$field);
        }
        $this->assertSame('Penerima yang diinput', $document->recipient_name);
        $this->assertSame('2026-10-06', $document->payment_date->format('Y-m-d'));
        $this->put(route('documents.update', $document), $this->data($extra + ['amount' => 1000]))->assertSessionHasNoErrors();
        $this->assertSame('001/REV.SMK', $document->fresh()->voucher_number);
        $this->assertSame('002/REV.SMK', VoucherNumber::preview());
        $this->get(route('documents.voucher', $document))->assertOk()->assertSee('/REV.SMK')->assertDontSee('001/REV.SMK')
            ->assertSee('Yayuk Sri Mulyani Rahayu')->assertSee('Riri Yulianti Solfia')->assertSee('Penerima yang diinput');
    }

    public function test_payment_date_follows_receipt_date_and_number_remains_unchanged_on_edit(): void
    {
        $document = $this->createDocument();
        $this->put(route('documents.update', $document), $this->data(['receipt_date' => '2026-11-10', 'recipient_name' => 'Nama baru']))->assertSessionHasNoErrors();
        $this->assertSame('2026-11-10', $document->fresh()->payment_date->format('Y-m-d'));
        $this->assertSame('Nama baru', $document->fresh()->recipient_name);
        $this->assertSame('001/REV.SMK', $document->fresh()->voucher_number);
        $this->get(route('documents.create'))->assertOk()->assertSee('value="/REV.SMK"', false)->assertDontSee('002/REV.SMK')->assertDontSee('name="voucher_number"', false);
    }

    public function test_failed_transaction_does_not_consume_a_voucher_number(): void
    {
        try {
            DB::transaction(function () {
                Document::create($this->data());
                throw new \RuntimeException('Simulated failure');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('documents', 0);
        $this->assertSame('001/REV.SMK', VoucherNumber::preview());
        $this->assertSame('001/REV.SMK', $this->createDocument()->voucher_number);
    }

    public function test_internal_and_shared_prints_leave_number_and_day_blank_for_handwriting(): void
    {
        $this->post(route('documents.store'), $this->data(['receipt_date' => '2026-09-07']))->assertSessionHasNoErrors();
        $document = Document::firstOrFail();
        $this->post(route('documents.share', $document))->assertSessionHasNoErrors();
        $token = $document->fresh()->share_token;
        foreach ([route('documents.print', $document), route('documents.voucher', $document), route('shared.print', $token), route('shared.voucher', $token)] as $url) {
            $response = $this->get($url)->assertOk()->assertDontSee('001/REV.SMK')->assertSee('07 September 2026');
            $html = new \DOMDocument;
            @$html->loadHTML($response->getContent());
            $xpath = new \DOMXPath($html);
            $number = $xpath->query('//div[@class="voucher-number"]')->item(0);
            $date = $xpath->query('//span[@class="voucher-payment-line"]')->item(0);
            $this->assertSame('Nomor : /REV.SMK', trim($number->textContent));
            $this->assertSame('Lunas Dibayar : September 2026', trim($date->textContent));
            foreach (['voucher-manual-number', 'voucher-manual-day'] as $class) {
                $blanks = $xpath->query('//span[@class="'.$class.'"]');
                $this->assertCount(1, $blanks);
                $this->assertSame('', $blanks->item(0)->textContent);
            }
        }
        $this->get(route('documents.edit', $document))->assertOk()->assertSee('value="/REV.SMK"', false)
            ->assertSee('value="September 2026"', false)->assertSee('ditulis tangan');
        $this->assertSame('001/REV.SMK', $document->fresh()->voucher_number);
        $this->assertSame('2026-09-07', $document->fresh()->receipt_date->format('Y-m-d'));
    }

    public function test_recipient_nip_is_optional_and_has_no_label_when_blank(): void
    {
        $document = $this->createDocument();
        $this->assertNull($document->recipient_nip);
        $this->get(route('documents.voucher', $document))->assertOk()
            ->assertSee('<div class="recipient-nip-line"></div>', false);
        $this->put(route('documents.update', $document), $this->data(['recipient_nip' => '001234567890123456']))->assertSessionHasNoErrors();
        $this->assertSame('001234567890123456', $document->fresh()->recipient_nip);
        $this->get(route('documents.voucher', $document))->assertOk()->assertSee('001234567890123456');
        $this->put(route('documents.update', $document), $this->data(['recipient_nip' => '']))->assertSessionHasNoErrors();
        $this->assertNull($document->fresh()->recipient_nip);
        $this->get(route('documents.edit', $document))->assertOk()->assertSee('NIP penerima pembayaran');
    }
}
