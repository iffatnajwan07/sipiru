<?php

namespace App\Console\Commands;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[AsCommand(name: 'app:create-admin', description: 'Membuat akun admin (untuk production)')]
class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {--name=} {--email=} {--nip=}';

    public function handle(): int
    {
        $data = [
            'name' => $this->option('name') ?: $this->ask('Nama'),
            'email' => strtolower((string) ($this->option('email') ?: $this->ask('Email'))),
            'nip' => $this->option('nip') ?: $this->ask('NIP / ID internal (opsional)'),
            'password' => $this->secret('Password (min. 8, huruf besar-kecil & angka)'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'nip' => ['nullable', 'string', 'max:30', 'unique:users,nip'],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User($data);
        $user->user_type = UserType::Admin;
        $user->is_active = true;
        $user->save();

        $this->info("Admin {$user->email} berhasil dibuat. Login di /admin/login");

        return self::SUCCESS;
    }
}
