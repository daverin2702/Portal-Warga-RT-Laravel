<?php

namespace Database\Seeders;

use App\Models\CashTransaction;
use App\Models\Household;
use App\Models\User;
use App\Services\BillService;
use App\Services\PositionService;
use Illuminate\Database\Seeder;

/** Data contoh untuk mencoba aplikasi. Jangan dipakai untuk website sungguhan. */
class DemoSeeder extends Seeder
{
    public function run(PositionService $positions, BillService $bills): void
    {
        $families = [
            ['Budi Santoso', 'A', '12', 4, 'owner', 'Jl. Mawar', 'Surabaya', '1980-05-14', 'Laki-laki', 'Karyawan Swasta', '081234567890'],
            ['Andi Wijaya', 'B', '7', 3, 'tenant', 'Jl. Melati', 'Malang', '1985-09-02', 'Laki-laki', 'Wiraswasta', '081211112222'],
            ['Siti Rahmawati', 'A', '3', 5, 'owner', 'Jl. Mawar', 'Sidoarjo', '1978-01-20', 'Perempuan', 'Guru', '085611223344'],
            ['Dewi Lestari', 'C', '1', 2, 'owner', 'Jl. Kenanga', 'Surabaya', '1990-11-11', 'Perempuan', 'Perawat', '081377788899'],
            ['Agus Prasetyo', 'C', '8', 4, 'tenant', 'Jl. Kenanga', 'Gresik', '1983-03-03', 'Laki-laki', 'Teknisi', '082144455566'],
            ['Rina Kurniawati', 'B', '15', 3, 'owner', 'Jl. Melati', 'Surabaya', '1988-07-07', 'Perempuan', 'Pedagang', '081899900011'],
        ];
        $accounts = [
            // [username, kata sandi, jabatan] sesuai urutan keluarga di atas
            ['rt', 'rt12345', 'ketua'], ['wakil', 'wakil123', 'wakil'], ['sekretaris', 'sekretaris123', 'sekretaris'],
            ['bendahara', 'bendahara123', 'bendahara'], ['agus', 'warga123', 'tim_keamanan'], ['rina', 'warga123', 'warga'],
        ];

        foreach ($families as $i => [$name, $block, $no, $members, $status, $street, $place, $birth, $gender, $job, $phone]) {
            $household = Household::query()->firstOrCreate(['head_name' => $name, 'block' => $block, 'house_number' => $no], [
                'members_count' => $members, 'residency_status' => $status, 'street' => $street, 'birth_place' => $place,
                'birth_date' => $birth, 'gender' => $gender, 'religion' => 'Islam', 'occupation' => $job, 'phone' => $phone,
                'emergency_contact_name' => 'Keluarga '.$name, 'emergency_contact_phone' => $phone,
            ]);
            if (! isset($accounts[$i])) {
                continue;
            }
            [$username, $password, $position] = $accounts[$i];
            $user = User::query()->firstOrCreate(['username' => $username], [
                'name' => $name, 'password' => $password, 'role' => 'warga', 'position' => 'warga',
                'household_id' => $household->id, 'must_change_password' => false,
            ]);
            $positions->assign($user, $position);
        }
    
        // Keuangan contoh: jenis tagihan bawaan, tagihan wajib bulan ini, saldo awal, dan satu pengeluaran disetujui.
        $this->call(BillTypeSeeder::class);
        $bills->issueMandatory(now()->format('Y-m'));
        CashTransaction::query()->firstOrCreate(['category' => 'Saldo awal', 'type' => 'masuk'], [
            'date' => now()->startOfMonth()->toDateString(), 'allocation' => 'Kas umum', 'description' => 'Saldo awal pembukuan',
            'amount' => 1500000, 'status' => 'disetujui',
        ]);
        CashTransaction::query()->firstOrCreate(['category' => 'Listrik pos ronda', 'type' => 'keluar'], [
            'date' => now()->startOfMonth()->toDateString(), 'allocation' => 'Keamanan', 'description' => 'Token listrik pos ronda',
            'amount' => 100000, 'status' => 'disetujui',
        ]);
    }
}
