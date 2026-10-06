<?php

namespace App\Support;

use Closure;

class AccountPassword
{
    public static function rules(): array
    {
        return [
            'required', 'string', 'min:8', 'max:72', 'confirmed', 'regex:/[a-z]/i', 'regex:/[0-9]/',
            function (string $attribute, mixed $value, Closure $fail) {
                if (is_string($value) && (strlen($value) > 72 || str_contains($value, "\0"))) {
                    $fail('Password terlalu panjang atau mengandung karakter yang tidak didukung.');
                }
            },
        ];
    }

    public static function messages(): array
    {
        return [
            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password harus berupa teks.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.max' => 'Password maksimal 72 karakter.',
            'password.regex' => 'Password harus berisi huruf dan angka.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
