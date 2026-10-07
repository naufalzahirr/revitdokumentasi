<?php

namespace App\Support;

class Asset
{
    public static function url(string $path): string
    {
        $file = public_path($path);
        $version = is_file($file) ? hash_file('sha256', $file) : false;

        return asset($path).($version ? '?v='.substr($version, 0, 16) : '');
    }
}
