<div align="center">

# 🏘️ Portal Warga RT · Laravel 13

**Administrasi RT modern: data warga, jabatan pengurus, dasbor, dan halaman depan yang bisa diatur sendiri.**

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-supported-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.8-7952B3?logo=bootstrap&logoColor=white)
![Status](https://img.shields.io/badge/status-Tahap%201-F2B705)

</div>

---

## 📌 Status

Ini adalah penulisan ulang **Portal Warga RT** (versi PHP murni) ke **Laravel 13**, dikerjakan **bertahap**.

| Tahap | Isi | Status |
|---|---|---|
| **1** | Fondasi, login per peran, jabatan & hak akses, dasbor + grafik, data warga, akun, impor Excel, pengaturan situs, tema, profil, struktur pengurus | ✅ Selesai |
| 2 | Surat: templat, editor A4, alur Sekretaris → Wakil → Ketua, tanda tangan QR/basah, cek keaslian | ⏳ Berikutnya |
| 3 | Keuangan: jenis tagihan (IPL, Kas RT), tagihan wajib, bukti bayar, kas, laporan keuangan | ⏳ |
| 4 | Pengumuman, aduan, notifikasi, tombol darurat, chat, perintah pindah data dari versi PHP | ⏳ |

---

## ✨ Fitur Tahap 1

- 🏠 **Halaman depan** yang seluruh isinya diatur dari menu admin: identitas, bagian utama, statistik, profil Ketua & Wakil, daftar layanan, footer
- 🔐 **Login per peran** (warga, pengurus, admin) dengan pembatasan 5 percobaan per menit
- 🧑‍🤝‍🧑 **Jabatan & hak akses**: 12 jabatan termasuk seksi dan tim; jabatan inti hanya satu orang dan otomatis berpindah saat diganti
- 📊 **Dasbor**: kartu statistik dan grafik KK per blok serta status hunian untuk pengurus; ringkasan keluarga untuk warga
- 👥 **Data warga**: cari, saring per blok, tambah/ubah/hapus (khusus Sekretaris & admin)
- 📥 **Impor Excel/CSV**: setiap baris jadi data keluarga + akun login (sandi bawaan wajib diganti)
- ⚙️ **Pengaturan situs** bertab dan **3 tema** (Teal Segar, Biru Resmi, Hangat Terakota)
- 🙋 **Profil Saya**: foto, nama, kata sandi, dan data keluarga
- 🏛️ **Struktur organisasi** otomatis dari jabatan akun
- 📴 **Aset offline**: Bootstrap, ikon, dan font sudah tersimpan di `public/vendor`

---

## 🚀 Instalasi

### Persyaratan
- **PHP 8.3 atau lebih baru** (XAMPP versi 8.3.x / 8.4.x / 8.5.x, atau Laragon)
- **Composer** — https://getcomposer.org
- MySQL / MariaDB
- Ekstensi PHP: `pdo_mysql`, `mbstring`, `fileinfo`, `zip` (untuk impor Excel)

> ⚠️ XAMPP dengan PHP 8.2 atau lebih lama **tidak bisa** menjalankan Laravel 13. Cek dengan `php -v`.

### Langkah
```bash
# 1. Pasang dependensi
composer install

# 2. Siapkan konfigurasi
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate

# 3. Buat database kosong "portal_warga" di phpMyAdmin, lalu sesuaikan DB_* di .env

# 4. Buat tabel + akun admin awal
php artisan migrate --seed

# (opsional) data contoh: 6 keluarga + akun pengurus
php artisan db:seed --class=DemoSeeder

# 5. Jalankan
php artisan serve             # buka http://localhost:8000
```

**Memakai XAMPP tanpa `artisan serve`:** letakkan folder di `htdocs/portal-warga`, buka `http://localhost/portal-warga/public`, dan isi `APP_URL=http://localhost/portal-warga/public` di `.env`.

### Akun awal

| Username | Kata sandi | Keterangan |
|---|---|---|
| `admin` | `admin12345` | Diminta mengganti sandi saat pertama masuk |

Bisa diubah sebelum seeding lewat `.env`: `RT_ADMIN_USERNAME`, `RT_ADMIN_PASSWORD`.

**Akun data contoh** (`DemoSeeder`): `rt / rt12345` (Ketua), `wakil / wakil123`, `sekretaris / sekretaris123`, `bendahara / bendahara123`, `agus / warga123` (Tim Keamanan), `rina / warga123` (Warga).

---

## 🧑‍🤝‍🧑 Peran & Wewenang

Diatur di `config/rt.php` dan didaftarkan sebagai **Gate** di `AppServiceProvider`.

| Peran / jabatan | Tahap 1 |
|---|---|
| 🛠️ Administrator | Semua wewenang, akun & jabatan, impor, pengaturan, tema |
| 📝 Sekretaris / Wakil Sekretaris | Tambah, ubah, hapus data warga |
| 👑 Ketua · 🔍 Wakil · 💼 Bendahara | Melihat data warga dan dasbor pengurus (wewenang surat & keuangan aktif di tahap 2–3) |
| 🏡 Warga | Dasbor warga, profil & data keluarga sendiri, struktur pengurus |

---

## 🗄️ Rancangan Database (baru)

| Tabel | Isi |
|---|---|
| `users` | akun: `username`, `role` (admin/pengurus/warga), `position` (jabatan), `household_id`, `photo`, `must_change_password` |
| `households` | kepala keluarga: KK, NIK, lahir, `block`, `house_number`, `members_count`, `residency_status`, kontak darurat |
| `settings` | pengaturan situs kunci-nilai (nama, logo, tema, teks beranda) |
| `sessions`, `cache`, `jobs` | bawaan Laravel |

> Database versi PHP **tidak langsung kompatibel**. Perintah pemindahan data (`php artisan rt:pindah-data`) dijadwalkan di Tahap 4.

---

## 📁 Struktur Penting

```
app/
├── Http/Controllers/         Beranda, Login, Dasbor, Warga, Profil, Struktur
│   └── Admin/                Akun, Impor, Pengaturan, Tema
├── Http/Middleware/EnsurePasswordChanged.php
├── Models/                   User, Household, Setting
├── Services/                 PositionService, HouseholdImporter, SpreadsheetReader
├── Support/                  Board (profil Ketua/Wakil), Uploads
└── helpers.php               setting(), rt_name(), rt_asset(), upload_url(), ...
config/rt.php                 jabatan, wewenang, tema, nilai bawaan situs
resources/views/              Blade + Bootstrap 5
public/vendor/                aset offline
public/uploads/               logo & foto unggahan (tidak masuk Git)
```

---

## 🔐 Keamanan

- Sandi di-hash (cast `hashed`), sandi bawaan wajib diganti lewat middleware
- CSRF di setiap formulir, pembatasan percobaan login
- Hak akses lewat Gate per jabatan; admin tidak bisa menurunkan aksesnya sendiri
- Unggahan dibatasi jenis & ukuran

## ⚠️ Catatan

- Grafik dasbor memakai Chart.js dari CDN sampai berkas `public/vendor/chart/chart.umd.min.js` ditambahkan.
- Kode ini ditulis tanpa bisa dijalankan di lingkungan pembuatnya; uji pertama dilakukan di server Anda.
