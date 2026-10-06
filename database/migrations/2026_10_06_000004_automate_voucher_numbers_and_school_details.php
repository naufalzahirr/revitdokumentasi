<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_counters', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });

        DB::transaction(function () {
            $number = 0;
            foreach (DB::table('documents')->orderBy('id')->get(['id', 'receipt_date']) as $document) {
                $number++;
                DB::table('documents')->where('id', $document->id)->update(array_merge(config('voucher.fixed_fields'), [
                    'voucher_number' => str_pad((string) $number, 3, '0', STR_PAD_LEFT).'/REV.SMK',
                    'payment_date' => $document->receipt_date,
                ]));
            }
            DB::table('voucher_counters')->insert(['name' => 'expenditure', 'last_number' => $number]);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->unique('voucher_number');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropUnique(['voucher_number']);
        });
        Schema::dropIfExists('voucher_counters');
    }
};
