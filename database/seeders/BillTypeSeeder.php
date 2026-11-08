<?php

namespace Database\Seeders;

use App\Models\BillType;
use Illuminate\Database\Seeder;

/** Jenis tagihan bawaan. Nominal hanya contoh; sesuaikan di menu Jenis Tagihan. */
class BillTypeSeeder extends Seeder
{
    public function run(): void
    {
        BillType::query()->firstOrCreate(['name' => 'IPL'], [
            'description' => 'Iuran Pengelolaan Lingkungan: keamanan, kebersihan, dan sampah',
            'amount' => 100000, 'allocation' => 'IPL', 'mandatory' => true,
        ]);
        BillType::query()->firstOrCreate(['name' => 'Kas RT'], [
            'description' => 'Iuran kas RT untuk kegiatan dan operasional',
            'amount' => 25000, 'allocation' => 'Kas umum', 'mandatory' => true,
        ]);
    }
}
