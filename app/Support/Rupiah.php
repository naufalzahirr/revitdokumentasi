<?php

namespace App\Support;

class Rupiah
{
    public static function format(int $amount): string
    {
        return 'Rp. '.number_format($amount, 0, ',', '.');
    }

    public static function words(int $amount): string
    {
        if ($amount < 0 || $amount > 999999999999) {
            throw new \InvalidArgumentException('Nominal di luar rentang yang didukung.');
        }

        return ucfirst(self::spell($amount)).' rupiah';
    }

    private static function spell(int $amount): string
    {
        $words = ['nol', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        if ($amount < 12) {
            return $words[$amount];
        }
        if ($amount < 20) {
            return self::spell($amount - 10).' belas';
        }
        if ($amount < 100) {
            return self::join(intdiv($amount, 10), 'puluh', $amount % 10);
        }
        if ($amount < 200) {
            return 'seratus'.($amount % 100 ? ' '.self::spell($amount % 100) : '');
        }
        if ($amount < 1000) {
            return self::join(intdiv($amount, 100), 'ratus', $amount % 100);
        }
        if ($amount < 2000) {
            return 'seribu'.($amount % 1000 ? ' '.self::spell($amount % 1000) : '');
        }
        foreach ([1000000000 => 'miliar', 1000000 => 'juta', 1000 => 'ribu'] as $unit => $name) {
            if ($amount >= $unit) {
                return self::join(intdiv($amount, $unit), $name, $amount % $unit);
            }
        }

        return '';
    }

    private static function join(int $amount, string $unit, int $remainder): string
    {
        return self::spell($amount).' '.$unit.($remainder ? ' '.self::spell($remainder) : '');
    }
}
