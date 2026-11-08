<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Akun administrator awal. Ganti kata sandinya segera setelah masuk
     * (pengguna otomatis diarahkan ke Profil Saya untuk menggantinya).
     *
     * Data contoh (keluarga dan akun pengurus) bisa ditambahkan dengan:
     *   php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        User::query()->firstOrCreate(['username' => env('RT_ADMIN_USERNAME', 'admin')], [
            'name' => 'Administrator',
            'password' => env('RT_ADMIN_PASSWORD', 'admin12345'),
            'role' => 'admin',
            'position' => 'warga',
            'must_change_password' => true,
        ]);

        $this->call([LetterTemplateSeeder::class, BillTypeSeeder::class]);
    }
}
