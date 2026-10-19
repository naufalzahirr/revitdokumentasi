<?php

namespace App\Support;

use Illuminate\Support\Collection;

class HandoverReport
{
    public static function pages(Collection $items): Collection
    {
        $pages = collect();
        $rows = collect();
        $lines = 0;

        foreach ($items->values() as $index => $item) {
            // Reserve room for wrapped names, the letterhead, and both signatures on A4.
            $cost = 1 + max(1, (int) ceil(mb_strwidth($item->name) / 28));
            if ($rows->isNotEmpty() && $lines + $cost > 12) {
                $pages->push($rows);
                $rows = collect();
                $lines = 0;
            }
            $rows->push(['number' => $index + 1, 'name' => $item->name]);
            $lines += $cost;
        }

        if ($rows->isNotEmpty() || $pages->isEmpty()) {
            $pages->push($rows);
        }

        return $pages;
    }
}
