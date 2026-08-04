<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('app.admin_email');
        $password = config('app.admin_password');

        if (blank($password)) {
            if (app()->isProduction()) {
                throw new RuntimeException('ADMIN_PASSWORD wajib diisi di file .env sebelum menjalankan seeder di production.');
            }

            $password = Str::password(16);
            $this->command?->warn("Password admin dibuat otomatis: {$password}");
        }

        $admin = User::firstOrNew(['email' => $email]);
        $admin->name = 'Admin ThafhanClothes';
        $admin->password = Hash::make($password);
        $admin->is_admin = true;
        $admin->save();
    }
}
