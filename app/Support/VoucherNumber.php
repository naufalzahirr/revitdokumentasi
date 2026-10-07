<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class VoucherNumber
{
    public static function next(): string
    {
        // Serialize allocations until the surrounding document transaction commits.
        return DB::transaction(function () {
            DB::table('voucher_counters')->where('name', 'expenditure')->increment('last_number');
            $number = self::firstAvailable(lock: true);
            DB::table('voucher_counters')->where('name', 'expenditure')->update(['last_number' => $number]);

            return self::format($number);
        });
    }

    public static function preview(): string
    {
        return self::format(self::firstAvailable());
    }

    private static function firstAvailable(bool $lock = false): int
    {
        $query = DB::table('documents')->select('voucher_number');
        if ($lock) {
            // A current read includes allocations committed while waiting for the counter lock.
            $query->lockForUpdate();
        }
        $used = array_fill_keys($query->pluck('voucher_number')->all(), true);
        $number = 1;
        while (isset($used[self::format($number)])) {
            $number++;
        }

        return $number;
    }

    public static function format(int $number): string
    {
        return str_pad((string) $number, 3, '0', STR_PAD_LEFT).'/REV.SMK';
    }
}
