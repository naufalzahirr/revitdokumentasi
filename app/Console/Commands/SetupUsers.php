<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AccountPassword;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SetupUsers extends Command
{
    protected $signature = 'revita:setup-users {--admin=admin : Username admin} {--password-stdin : Baca password awal dari stdin}';

    protected $description = 'Buat akun admin dan enam pengelola awal tanpa menimpa akun atau password yang sudah ada';

    public function handle(): int
    {
        $admin = Str::lower((string) $this->option('admin'));
        $names = ['naufalzahirr', 'riri', 'yayuk', 'azizul', 'purnamawati', 'rika'];
        if (! preg_match('/^[a-z0-9][a-z0-9._-]{0,49}$/', $admin) || in_array($admin, $names, true)) {
            $this->error('Username admin tidak valid atau sama dengan salah satu username pengelola.');

            return self::FAILURE;
        }
        $password = $this->option('password-stdin') ? rtrim(stream_get_contents(STDIN), "\r\n") : $this->secret('Password awal untuk akun baru (minimal 8 karakter, huruf dan angka)');
        $validator = Validator::make(['password' => $password, 'password_confirmation' => $password], ['password' => AccountPassword::rules()], AccountPassword::messages());
        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));

            return self::FAILURE;
        }
        $created = DB::transaction(function () use ($admin, $names, $password) {
            $created = [];
            foreach ([$admin, ...$names] as $username) {
                if (User::where('username', $username)->exists()) {
                    continue;
                }
                User::create([
                    'username' => $username, 'name' => $username === $admin ? 'Administrator' : ucfirst($username),
                    'email' => Str::uuid().'@revita.invalid', 'password' => $password,
                    'role' => $username === $admin ? 'admin' : 'editor', 'is_active' => true, 'must_change_password' => true,
                ]);
                $created[] = $username;
            }

            return $created;
        });
        $this->info(count($created).' akun baru dibuat. Akun yang sudah ada tidak diubah.');
        foreach ($created as $username) {
            $this->line($username.' — '.($username === $admin ? 'Admin' : 'Pengelola'));
        }

        return self::SUCCESS;
    }
}
