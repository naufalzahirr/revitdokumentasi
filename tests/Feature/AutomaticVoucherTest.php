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

    private function data(array $extra = []): array
    {
        return array_merge(['category' => 'Renovasi', 'receipt_date' => '2026-10-06', 'receipt_number' => 'NT-001'], $extra);
    }

    private function createDocument(): Document
    {
        $this->post(route('documents.store'), $this->data())->assertSessionHasNoErrors();

        return Document::latest('id')->firstOrFail();
    }

    public function test_voucher_numbers_start_at_one_and_are_not_reused_after_deletion(): void
    {
        $this->assertSame('001/REV.SMK', VoucherNumber::preview());
        $first = $this->createDocument();
        $second = $this->createDocument();
        $this->assertSame('001/REV.SMK', $first->voucher_number);
        $this->assertSame('002/REV.SMK', $second->voucher_number);
        $this->delete(route('documents.destroy', $second))->assertRedirect();
        $third = $this->createDocument();
        $this->assertSame('003/REV.SMK', $third->voucher_number);
        $this->assertSame('004/REV.SMK', VoucherNumber::preview());
        $this->assertSame('1000/REV.SMK', VoucherNumber::format(1000));
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
        $this->get(route('documents.voucher', $document))->assertOk()->assertSee('001/REV.SMK')
            ->assertSee('Yayuk Sri Mulyani Rahayu')->assertSee('Riri Yulianti Solfia')->assertSee('Penerima yang diinput');
    }

    public function test_payment_date_follows_receipt_date_and_number_remains_unchanged_on_edit(): void
    {
        $document = $this->createDocument();
        $this->put(route('documents.update', $document), $this->data(['receipt_date' => '2026-11-10', 'recipient_name' => 'Nama baru']))->assertSessionHasNoErrors();
        $this->assertSame('2026-11-10', $document->fresh()->payment_date->format('Y-m-d'));
        $this->assertSame('Nama baru', $document->fresh()->recipient_name);
        $this->assertSame('001/REV.SMK', $document->fresh()->voucher_number);
        $this->get(route('documents.create'))->assertOk()->assertSee('002/REV.SMK')->assertDontSee('name="voucher_number"', false);
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

    public function test_recipient_nip_is_optional_and_keeps_its_space_in_print(): void
    {
        $document = $this->createDocument();
        $this->assertNull($document->recipient_nip);
        $this->get(route('documents.voucher', $document))->assertOk()
            ->assertSee('<div class="recipient-nip-line">NIP <span></span></div>', false);
        $this->put(route('documents.update', $document), $this->data(['recipient_nip' => '001234567890123456']))->assertSessionHasNoErrors();
        $this->assertSame('001234567890123456', $document->fresh()->recipient_nip);
        $this->get(route('documents.voucher', $document))->assertOk()->assertSee('001234567890123456');
        $this->put(route('documents.update', $document), $this->data(['recipient_nip' => '']))->assertSessionHasNoErrors();
        $this->assertNull($document->fresh()->recipient_nip);
        $this->get(route('documents.edit', $document))->assertOk()->assertSee('NIP penerima pembayaran');
    }
}
