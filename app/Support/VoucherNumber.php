<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class VoucherNumber
{
    public static function next(): string
    {
        // Increment first: this locks the counter row until the transaction commits.
        return DB::transaction(function () {
            DB::table('voucher_counters')->where('name', 'expenditure')->increment('last_number');

            return self::format((int) DB::table('voucher_counters')->where('name', 'expenditure')->value('last_number'));
        });
    }

    public static function preview(): string
    {
        return self::format((int) DB::table('voucher_counters')->where('name', 'expenditure')->value('last_number') + 1);
    }

    public static function format(int $number): string
    {
        return str_pad((string) $number, 3, '0', STR_PAD_LEFT).'/REV.SMK';
    }
}
