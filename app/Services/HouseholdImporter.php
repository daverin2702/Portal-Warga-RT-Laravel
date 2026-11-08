<?php

namespace App\Services;

use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

/**
 * Impor massal warga: setiap baris membuat data keluarga + akun login (role warga)
 * dengan kata sandi bawaan dan kewajiban mengganti sandi saat pertama masuk.
 */
class HouseholdImporter
{
    private const HEADERS = [
        'nama' => 'name', 'namalengkap' => 'name', 'namakepalakeluarga' => 'name',
        'blok' => 'block', 'norumah' => 'house', 'nomorrumah' => 'house', 'no' => 'house',
        'nohp' => 'phone', 'nomorhp' => 'phone', 'hp' => 'phone', 'telepon' => 'phone', 'telp' => 'phone',
        'notelp' => 'phone', 'nohandphone' => 'phone', 'whatsapp' => 'phone', 'wa' => 'phone',
        'email' => 'email', 'nokk' => 'kk', 'nomorkk' => 'kk', 'kk' => 'kk', 'nik' => 'nik',
        'alamat' => 'street', 'jalan' => 'street',
    ];

    /**
     * @return array{created: array<int, array>, skipped: array<int, array>, error?: string}
     */
    public function import(array $rows): array
    {
        $header = array_shift($rows) ?? [];
        $columns = [];
        foreach ($header as $i => $label) {
            $key = self::HEADERS[preg_replace('/[^a-z0-9]/', '', Str::lower((string) $label))] ?? null;
            if ($key && ! in_array($key, $columns, true)) {
                $columns[$i] = $key;
            }
        }
        if (! in_array('name', $columns, true) || ! in_array('house', $columns, true)) {
            return ['created' => [], 'skipped' => [], 'error' => 'Baris judul tidak dikenali. Gunakan templat (kolom Nama, Blok, No Rumah, No HP, Email).'];
        }

        $created = [];
        $skipped = [];
        $password = Hash::make(config('rt.import_password'));
        foreach ($rows as $n => $row) {
            $line = $n + 2;
            $d = [];
            foreach ($columns as $i => $key) {
                $d[$key] = trim((string) ($row[$i] ?? ''));
            }
            if (! array_filter($d) || Str::startsWith(Str::upper($d['name'] ?? ''), 'CONTOH')) {
                continue;
            }
            if (($d['name'] ?? '') === '' || ($d['house'] ?? '') === '') {
                $skipped[] = [$line, $d['name'] ?: '-', 'Nama atau nomor rumah kosong'];
                continue;
            }
            $block = Str::upper($d['block'] ?? '') ?: null;
            $phone = $this->normalizePhone($d['phone'] ?? '');
            $email = filter_var($d['email'] ?? '', FILTER_VALIDATE_EMAIL) ? Str::lower($d['email']) : null;

            $exists = Household::query()->where('head_name', $d['name'])->where('block', $block)->where('house_number', $d['house'])->exists();
            if ($exists) {
                $skipped[] = [$line, $d['name'], 'Sudah terdaftar'];
                continue;
            }

            try {
                $username = DB::transaction(function () use ($d, $block, $phone, $email, $password) {
                    $household = Household::query()->create([
                        'head_name' => $d['name'], 'block' => $block, 'house_number' => $d['house'],
                        'phone' => $phone ?: null, 'email' => $email, 'street' => ($d['street'] ?? '') ?: null,
                        'family_card_number' => ($d['kk'] ?? '') ?: null, 'head_nik' => ($d['nik'] ?? '') ?: null,
                    ]);
                    $username = $this->uniqueUsername($phone ?: ($email ? Str::before($email, '@') : Str::lower(Str::slug($d['name'], '')).Str::lower((string) $block).$d['house']));
                    // $password sudah di-hash sekali untuk semua baris; cast "hashed" tidak meng-hash ulang.
                    User::query()->create([
                        'name' => $d['name'], 'username' => $username, 'password' => $password,
                        'email' => $email && ! User::query()->where('email', $email)->exists() ? $email : null,
                        'role' => 'warga', 'position' => 'warga', 'household_id' => $household->id, 'must_change_password' => true,
                    ]);

                    return $username;
                });
                $created[] = [$d['name'], $block, $d['house'], $username];
            } catch (Throwable $e) {
                report($e);
                $skipped[] = [$line, $d['name'], 'Gagal disimpan (nomor KK mungkin ganda)'];
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /** "+62 812-3456" / "8123456" / "6.28123E+11" menjadi "08123456..." */
    public function normalizePhone(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^[0-9.]+E\+?[0-9]+$/i', $value)) {
            $value = sprintf('%.0f', (float) $value);
        }
        $value = preg_replace('/\D/', '', $value);
        if ($value === '') {
            return '';
        }
        if (str_starts_with($value, '62')) {
            return '0'.substr($value, 2);
        }

        return $value[0] === '8' ? '0'.$value : $value;
    }

    private function uniqueUsername(string $base): string
    {
        $base = Str::limit(preg_replace('/[^a-z0-9_.]/', '', Str::lower($base)) ?: 'warga', 45, '');
        $username = $base;
        $i = 1;
        while (User::query()->where('username', $username)->exists()) {
            $username = $base.(++$i);
        }

        return $username;
    }
}
