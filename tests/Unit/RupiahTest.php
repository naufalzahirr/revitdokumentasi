<?php

namespace Tests\Unit;

use App\Support\Rupiah;
use PHPUnit\Framework\TestCase;

class RupiahTest extends TestCase
{
    public function test_words_cover_zero_teens_hundreds_thousands_and_large_amounts(): void
    {
        foreach ([0 => 'Nol', 11 => 'Sebelas', 12 => 'Dua belas', 100 => 'Seratus', 101 => 'Seratus satu',
            1000 => 'Seribu', 1011 => 'Seribu sebelas', 2000 => 'Dua ribu', 1000000 => 'Satu juta',
            1000000000 => 'Satu miliar', 2000000101 => 'Dua miliar seratus satu'] as $amount => $expected) {
            $this->assertSame($expected.' rupiah', Rupiah::words($amount));
        }
        $this->assertSame('Rp. 6.150.328', Rupiah::format(6150328));
    }
}
