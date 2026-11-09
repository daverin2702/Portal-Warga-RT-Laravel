<?php

namespace App\Console\Commands;

use App\Models\Household;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Memindahkan isi database Portal Warga versi PHP murni (db_rt) ke struktur Laravel.
 * Database lama hanya dibaca. Hash kata sandi bcrypt dibawa apa adanya sehingga warga
 * tetap masuk dengan sandi lama. Chat, notifikasi, dan riwayat darurat tidak dipindah.
 */
class PindahData extends Command
{
    protected $signature = 'rt:pindah-data
        {--host=127.0.0.1 : Host database lama}
        {--port=3306 : Port database lama}
        {--database=db_rt : Nama database lama}
        {--username=root : Pengguna database lama}
        {--password= : Kata sandi database lama}
        {--uploads= : Folder app/uploads versi lama (untuk logo, foto, dan bukti bayar)}
        {--force : Tetap jalankan walau database baru sudah berisi data warga}';

    protected $description = 'Pindahkan data dari Portal Warga versi PHP (db_rt) ke versi Laravel';

    /** Peta id lama => id baru per tabel. */
    private array $map = [];

    private array $count = [];

    private ?string $uploads = null;

    /** Kunci pengaturan lama => kunci baru. */
    private const SETTINGS = [
        'nama_situs' => 'site_name', 'brand_sub' => 'brand_sub', 'tema' => 'theme', 'rt_nomor' => 'rt_number', 'rw_nomor' => 'rw_number',
        'kelurahan' => 'village', 'kecamatan' => 'district', 'kota' => 'city', 'motto' => 'motto',
        'kop1' => 'letterhead_line1', 'kop2' => 'letterhead_line2', 'kop3' => 'letterhead_line3', 'kop4' => 'letterhead_line4',
        'hero_badge' => 'hero_badge', 'hero_judul' => 'hero_title', 'hero_lead' => 'hero_lead', 'hero_btn1' => 'hero_button_primary',
        'hero_btn2' => 'hero_button_secondary', 'tampil_statistik' => 'show_stats', 'pengurus_judul' => 'board_title', 'pengurus_sub' => 'board_subtitle',
        'ketua_nama' => 'chair_name', 'ketua_masa' => 'chair_term', 'ketua_sambutan' => 'chair_message',
        'wakil_nama' => 'vice_name', 'wakil_masa' => 'vice_term', 'wakil_sambutan' => 'vice_message',
        'footer_kiri' => 'footer_left', 'footer_kanan' => 'footer_right', 'rekening' => 'payment_info', 'base_url' => 'public_url',
    ];

    /** Gambar pengaturan lama => [kunci baru, folder di public/uploads]. */
    private const IMAGES = [
        'logo' => ['logo', 'logo'], 'logo_kanan' => ['logo_right', 'logo'], 'favicon' => ['favicon', 'logo'],
        'hero_gambar' => ['hero_image', 'beranda'], 'ketua_foto' => ['chair_photo', 'pengurus'], 'wakil_foto' => ['vice_photo', 'pengurus'],
    ];

    public function handle(): int
    {
        config(['database.connections.lama' => [
            'driver' => 'mysql', 'host' => $this->option('host'), 'port' => $this->option('port'),
            'database' => $this->option('database'), 'username' => $this->option('username'), 'password' => (string) $this->option('password'),
            'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => false,
        ]]);

        try {
            $tables = collect(DB::connection('lama')->select('SHOW TABLES'))->map(fn ($r) => array_values((array) $r)[0])->all();
        } catch (Throwable $e) {
            $this->error('Tidak bisa terhubung ke database lama: '.$e->getMessage());

            return self::FAILURE;
        }
        if (! in_array('warga', $tables, true) || ! in_array('users', $tables, true)) {
            $this->error('Database "'.$this->option('database').'" tidak tampak seperti database Portal Warga versi PHP (tabel warga/users tidak ada).');

            return self::FAILURE;
        }
        if (Household::query()->exists() && ! $this->option('force')) {
            $this->error('Database baru sudah berisi data warga. Kosongkan dulu (php artisan migrate:fresh --seed) atau tambahkan --force.');

            return self::FAILURE;
        }

        if ($dir = $this->option('uploads')) {
            $this->uploads = rtrim($dir, '/\\');
            if (! is_dir($this->uploads)) {
                $this->warn('Folder unggahan "'.$dir.'" tidak ditemukan. Data tetap dipindah tanpa berkas gambar dan bukti bayar.');
                $this->uploads = null;
            }
        } else {
            $this->warn('Opsi --uploads tidak diisi: logo, foto, dan bukti bayar tidak ikut disalin.');
        }

        $has = fn (string $t) => in_array($t, $tables, true);

        try {
            DB::transaction(function () use ($has) {
                $this->households();
                $this->users();
                if ($has('pengaturan')) {
                    $this->settings();
                }
                if ($has('templat_surat')) {
                    $this->templates();
                }
                if ($has('surat_terbit')) {
                    $this->letters();
                }
                if ($has('surat')) {
                    $this->letterRequests($has('surat_terbit'));
                }
                if ($has('jenis_tagihan')) {
                    $this->billTypes();
                }
                if ($has('tagihan')) {
                    $this->bills();
                }
                if ($has('kas')) {
                    $this->cash();
                }
                if ($has('pengumuman')) {
                    $this->announcements();
                }
                if ($has('aduan')) {
                    $this->complaints();
                }
            });
        } catch (Throwable $e) {
            $this->error('Pemindahan dibatalkan, tidak ada data yang tersimpan: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(['Data', 'Jumlah'], collect($this->count)->map(fn ($n, $k) => [$k, $n])->values()->all());
        $this->info('Selesai. Warga masuk dengan username dan kata sandi lama.');

        return self::SUCCESS;
    }

    private function old(string $table)
    {
        return DB::connection('lama')->table($table)->orderBy(in_array($table, ['pengaturan'], true) ? 'k' : 'id')->cursor();
    }

    private function tally(string $label, int $n = 1): void
    {
        $this->count[$label] = ($this->count[$label] ?? 0) + $n;
    }

    private function households(): void
    {
        foreach ($this->old('warga') as $w) {
            $kk = $this->blank($w->no_kk ?? null);
            if ($kk && ($id = DB::table('households')->where('family_card_number', $kk)->value('id'))) {
                $this->map['warga'][$w->id] = $id;   // KK sudah ada (mis. dijalankan ulang dengan --force)
                $this->tally('Keluarga dilewati (KK sudah ada)');

                continue;
            }
            $this->map['warga'][$w->id] = DB::table('households')->insertGetId([
                'head_name' => $w->nama_kk, 'family_card_number' => $kk, 'head_nik' => $this->blank($w->nik_kepala ?? null),
                'birth_place' => $this->blank($w->tempat_lahir ?? null), 'birth_date' => $this->date($w->tgl_lahir ?? null),
                'gender' => $this->blank($w->jenis_kelamin ?? null), 'religion' => $this->blank($w->agama ?? null),
                'occupation' => $this->blank($w->pekerjaan ?? null), 'street' => $this->blank($w->alamat ?? null),
                'block' => $this->blank($w->blok ?? null), 'house_number' => $this->blank($w->no_rumah ?? null),
                'members_count' => max(1, (int) ($w->jml_anggota ?? 1)),
                'residency_status' => ($w->status_hunian ?? '') === 'penyewa' ? 'tenant' : 'owner',
                'phone' => $this->blank($w->telp ?? null), 'email' => $this->blank($w->email ?? null),
                'emergency_contact_name' => $this->blank($w->darurat_nama ?? null), 'emergency_contact_phone' => $this->blank($w->darurat_telp ?? null),
                'created_at' => $w->created_at ?? now(), 'updated_at' => now(),
            ]);
            $this->tally('Keluarga');
        }
    }

    private function users(): void
    {
        $positions = array_keys(config('rt.positions'));
        foreach ($this->old('users') as $u) {
            $role = ['admin' => 'admin', 'rt' => 'pengurus'][$u->role] ?? 'warga';
            $position = in_array($u->jabatan ?? '', $positions, true) ? $u->jabatan : 'warga';
            if ($role === 'admin') {
                $position = 'warga';
            }
            $row = [
                'name' => $u->nama, 'password' => $u->password, 'role' => $role, 'position' => $position,
                'household_id' => $u->warga_id ? ($this->map['warga'][$u->warga_id] ?? null) : null,
                'photo' => $this->copyPublic($u->foto ?? null, 'foto'),
                'must_change_password' => (bool) ($u->ganti_sandi ?? false), 'updated_at' => now(),
            ];
            if ($id = DB::table('users')->where('username', $u->username)->value('id')) {
                DB::table('users')->where('id', $id)->update($row);   // mis. akun admin bawaan seeder ditimpa akun lama
                $this->tally('Akun diperbarui (username sama)');
            } else {
                $id = DB::table('users')->insertGetId($row + ['username' => $u->username, 'created_at' => now()]);
                $this->tally('Akun');
            }
            $this->map['users'][$u->id] = $id;
            if ($u->warga_id) {
                $this->map['user_of_warga'][$u->warga_id] ??= $id;
            }
        }
        // Jabatan tunggal: jika ada dua pemegang, yang terakhir dipindah yang dipakai.
        foreach (config('rt.single_positions') as $p) {
            $ids = DB::table('users')->where('position', $p)->orderByDesc('id')->pluck('id');
            if ($ids->count() > 1) {
                DB::table('users')->whereIn('id', $ids->slice(1))->update(['position' => 'warga']);
            }
        }
    }

    private function settings(): void
    {
        $old = DB::connection('lama')->table('pengaturan')->pluck('v', 'k');
        $save = [];
        foreach (self::SETTINGS as $from => $to) {
            if (isset($old[$from]) && $old[$from] !== '') {
                $save[$to] = $old[$from];
            }
        }
        if (! empty($old['kop_alamat'])) {
            $save['secretariat_address'] = trim(preg_replace('/^\s*sekretariat\s*:\s*/i', '', $old['kop_alamat']));
        }
        if (isset($old['profil_sumber'])) {
            $save['profile_source'] = $old['profil_sumber'] === 'manual' ? 'manual' : 'account';
        }
        if (isset($save['theme']) && ! array_key_exists($save['theme'], config('rt.themes'))) {
            unset($save['theme']);
        }
        if (! empty($old['fitur_json']) && is_array($f = json_decode($old['fitur_json'], true))) {
            $save['features'] = json_encode(array_values(array_map(fn ($x) => [
                'icon' => in_array($x['i'] ?? '', config('rt.feature_icons'), true) ? $x['i'] : 'bi-star',
                'title' => (string) ($x['j'] ?? ''), 'text' => (string) ($x['d'] ?? ''),
            ], array_filter($f, 'is_array'))), JSON_UNESCAPED_UNICODE);
        }
        foreach (self::IMAGES as $from => [$to, $folder]) {
            if (! empty($old[$from]) && ($path = $this->copyPublic($old[$from], $folder))) {
                $save[$to] = $path;
            }
        }
        Setting::put($save);   // sekaligus menghapus cache pengaturan
        $this->tally('Pengaturan situs', count($save));
    }

    private function templates(): void
    {
        foreach ($this->old('templat_surat') as $t) {
            $fields = collect(json_decode($t->input_json ?? '', true) ?: [])->filter(fn ($f) => is_array($f) && ! empty($f['key']))
                ->map(fn ($f) => ['key' => (string) $f['key'], 'label' => (string) ($f['label'] ?? $f['key']),
                    'type' => in_array($f['type'] ?? 'text', ['text', 'textarea', 'date', 'number'], true) ? $f['type'] : 'text',
                    'required' => (bool) ($f['wajib'] ?? $f['required'] ?? false)])->values()->all();
            $row = ['body' => $t->isi, 'fields' => json_encode($fields, JSON_UNESCAPED_UNICODE), 'updated_at' => now()];
            if ($id = DB::table('letter_templates')->where('name', $t->nama)->value('id')) {
                DB::table('letter_templates')->where('id', $id)->update($row);
            } else {
                $id = DB::table('letter_templates')->insertGetId($row + ['name' => $t->nama, 'created_at' => $t->created_at ?? now()]);
            }
            $this->map['templat'][$t->id] = $id;
            $this->tally('Templat surat');
        }
    }

    private function letters(): void
    {
        foreach ($this->old('surat_terbit') as $s) {
            if (DB::table('letters')->where('token', $s->token)->exists()) {
                continue;
            }
            $created = $s->created_at ?? now();
            $this->map['surat_terbit'][$s->id] = DB::table('letters')->insertGetId([
                'letter_template_id' => $s->templat_id ? ($this->map['templat'][$s->templat_id] ?? null) : null,
                'household_id' => $s->warga_id ? ($this->map['warga'][$s->warga_id] ?? null) : null,
                'number' => $s->nomor, 'title' => $s->judul, 'body' => $s->isi, 'purpose' => $this->blank($s->keperluan ?? null),
                'with_letterhead' => (bool) $s->pakai_kop, 'qr_signature' => (bool) ($s->ttd_qr ?? true),
                'status' => $s->status ?: 'terbit', 'token' => $s->token, 'signer_name' => $this->blank($s->penandatangan ?? null),
                'issued_at' => ($s->status ?: 'terbit') === 'terbit' ? $created : null,
                'log' => $this->log($s->alur ?? null), 'created_at' => $created, 'updated_at' => now(),
            ]);
            if ($s->surat_id ?? null) {
                $this->map['letter_of_surat'][$s->surat_id] = $this->map['surat_terbit'][$s->id];
            }
            $this->tally('Surat terbit / draf');
        }
    }

    private function letterRequests(bool $withLetters): void
    {
        $templates = DB::table('letter_templates')->pluck('id', 'name');
        foreach ($this->old('surat') as $s) {
            if (! ($household = $this->map['warga'][$s->warga_id] ?? null)) {
                continue;
            }
            $values = json_decode($s->data_json ?? '', true);
            DB::table('letter_requests')->insert([
                'household_id' => $household, 'user_id' => $this->map['user_of_warga'][$s->warga_id] ?? null,
                'letter_template_id' => $templates[$s->jenis] ?? null, 'template_name' => Str::limit($s->jenis, 100, ''),
                'purpose' => $s->keperluan, 'field_values' => json_encode(is_array($values) ? $values : [], JSON_UNESCAPED_UNICODE),
                'status' => $s->status, 'note' => $this->blank($s->catatan ?? null),
                'letter_id' => $withLetters ? ($this->map['letter_of_surat'][$s->id] ?? null) : null,
                'log' => $this->log($s->alur ?? null), 'created_at' => $s->created_at ?? now(), 'updated_at' => now(),
            ]);
            $this->tally('Pengajuan surat');
        }
    }

    private function billTypes(): void
    {
        foreach ($this->old('jenis_tagihan') as $j) {
            $row = ['description' => $this->blank($j->keterangan ?? null), 'amount' => (int) $j->nominal, 'allocation' => $j->alokasi ?: 'Kas umum',
                'mandatory' => (bool) $j->wajib, 'active' => (bool) $j->aktif, 'updated_at' => now()];
            if ($id = DB::table('bill_types')->where('name', $j->nama)->value('id')) {
                DB::table('bill_types')->where('id', $id)->update($row);
            } else {
                DB::table('bill_types')->insert($row + ['name' => $j->nama, 'created_at' => now()]);
            }
            $this->tally('Jenis tagihan');
        }
    }

    private function bills(): void
    {
        $types = DB::table('bill_types')->pluck('id', 'name');
        foreach ($this->old('tagihan') as $t) {
            if (! ($household = $this->map['warga'][$t->warga_id] ?? null)) {
                continue;
            }
            if (DB::table('bills')->where(['household_id' => $household, 'name' => $t->jenis, 'period' => $t->periode])->exists()) {
                $this->tally('Tagihan dilewati (sudah ada)');

                continue;
            }
            $receipt = $this->blank($t->no_struk ?? null);
            if ($receipt && DB::table('bills')->where('receipt_number', $receipt)->exists()) {
                $receipt = null;
            }
            $this->map['tagihan'][$t->id] = DB::table('bills')->insertGetId([
                'household_id' => $household, 'bill_type_id' => $types[$t->jenis] ?? null, 'name' => $t->jenis, 'period' => $t->periode,
                'allocation' => $t->alokasi ?: 'Kas umum', 'amount' => (int) $t->nominal, 'status' => $t->status,
                'payment_method' => $this->blank($t->metode ?? null), 'proof_path' => $this->copyProof($t->bukti ?? null),
                'proof_uploaded_at' => $t->tgl_upload ?? null, 'rejection_note' => $this->blank($t->catatan ?? null),
                'receipt_number' => $receipt, 'paid_at' => $t->tgl_bayar ?? null, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->tally('Tagihan');
        }
    }

    private function cash(): void
    {
        foreach ($this->old('kas') as $k) {
            DB::table('cash_transactions')->insert([
                'date' => $k->tanggal, 'type' => $k->tipe, 'category' => $k->kategori, 'allocation' => $k->alokasi ?: 'Kas umum',
                'description' => $this->blank($k->keterangan ?? null), 'amount' => (int) $k->nominal, 'status' => $k->status ?: 'disetujui',
                'bill_id' => $k->tagihan_id ? ($this->map['tagihan'][$k->tagihan_id] ?? null) : null,
                'log' => $this->log($k->alur ?? null), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->tally('Transaksi kas');
        }
    }

    private function announcements(): void
    {
        foreach ($this->old('pengumuman') as $p) {
            DB::table('announcements')->insert(['title' => $p->judul, 'body' => $p->isi, 'category' => $p->kategori ?: 'lainnya',
                'created_at' => $p->created_at ?? now(), 'updated_at' => $p->created_at ?? now()]);
            $this->tally('Pengumuman');
        }
    }

    private function complaints(): void
    {
        foreach ($this->old('aduan') as $a) {
            if (! ($household = $this->map['warga'][$a->warga_id] ?? null)) {
                continue;
            }
            DB::table('complaints')->insert([
                'household_id' => $household, 'user_id' => $this->map['user_of_warga'][$a->warga_id] ?? null,
                'category' => $a->kategori, 'title' => $a->judul, 'description' => $a->deskripsi, 'status' => $a->status,
                'response' => $this->blank($a->tanggapan ?? null), 'created_at' => $a->created_at ?? now(), 'updated_at' => now(),
            ]);
            $this->tally('Aduan');
        }
    }

    // ---------- bantuan ----------

    private function blank($v): ?string
    {
        $v = is_string($v) ? trim($v) : $v;

        return $v === '' || $v === null ? null : (string) $v;
    }

    private function date($v): ?string
    {
        return $v && ! str_starts_with((string) $v, '0000') ? (string) $v : null;
    }

    /** Teks alur lama "dd/mm/YYYY HH:ii - keterangan" per baris menjadi larik log JSON. */
    private function log(?string $text): ?string
    {
        if (! $text) {
            return null;
        }
        $log = [];
        foreach (preg_split('/\R/', trim($text)) as $line) {
            if (preg_match('~^(\d{2}/\d{2}/\d{4} \d{2}:\d{2})\s*-\s*(.+)$~', trim($line), $m)) {
                $log[] = ['at' => $m[1], 'by' => '', 'action' => $m[2], 'note' => ''];
            } elseif (trim($line) !== '') {
                $log[] = ['at' => '', 'by' => '', 'action' => trim($line), 'note' => ''];
            }
        }

        return json_encode($log, JSON_UNESCAPED_UNICODE);
    }

    /** Salin gambar lama ke public/uploads/<folder>/ dan kembalikan jalur barunya. */
    private function copyPublic(?string $file, string $folder): ?string
    {
        $file = $file ? basename($file) : '';
        if (! $file || ! $this->uploads || ! is_file($src = $this->uploads.DIRECTORY_SEPARATOR.$file)) {
            return null;
        }
        $path = $folder.'/'.$file;
        Storage::disk('uploads')->put($path, File::get($src));
        $this->tally('Berkas gambar disalin');

        return $path;
    }

    /** Salin bukti bayar lama (app/uploads/bukti) ke penyimpanan privat. */
    private function copyProof(?string $file): ?string
    {
        $file = $file ? basename($file) : '';
        if (! $file || ! $this->uploads) {
            return null;
        }
        $src = $this->uploads.DIRECTORY_SEPARATOR.'bukti'.DIRECTORY_SEPARATOR.$file;
        if (! is_file($src)) {
            return null;
        }
        $path = 'bukti-bayar/'.$file;
        Storage::disk('local')->put($path, File::get($src));
        $this->tally('Bukti bayar disalin');

        return $path;
    }
}
