<div align="center">

# 🏘️ Portal Warga RT · Laravel 13

**Administrasi RT modern: data warga, jabatan pengurus, surat ber-QR, iuran & kas yang transparan, pengumuman, aduan, tombol darurat, chat, CCTV live, dan halaman depan yang bisa diatur sendiri.**

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-supported-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.8-7952B3?logo=bootstrap&logoColor=white)
![Status](https://img.shields.io/badge/status-Tahap%205%20selesai-2EA043)

</div>

---

## 📌 Status

Ini adalah penulisan ulang **Portal Warga RT** (versi PHP murni) ke **Laravel 13**, dikerjakan **bertahap**.

| Tahap | Isi | Status |
|---|---|---|
| **1** | Fondasi, login per peran, jabatan & hak akses, dasbor + grafik, data warga, akun, impor Excel, pengaturan situs, tema, profil, struktur pengurus | ✅ Selesai |
| **2** | Surat: templat, editor A4, alur Sekretaris → Wakil → Ketua, tanda tangan QR/basah, cetak berkop, cek keaslian | ✅ Selesai |
| **3** | Keuangan: jenis tagihan (IPL, Kas RT), tagihan wajib, bukti bayar, struk, kas, persetujuan pengeluaran, laporan keuangan | ✅ Selesai |
| **4** | Pengumuman, aduan, notifikasi per peran, tombol darurat, chat pop-up, perintah pindah data dari versi PHP | ✅ Selesai |
| **5** | CCTV: kelola kamera (penempatan, nama, koneksi), live streaming untuk semua peran, RTSP via gateway, HLS, MJPEG, snapshot, embed pihak ketiga, YouTube | ✅ Selesai |

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

## ✉️ Fitur Tahap 2 — Surat

- 📝 **7 templat bawaan**: Pengantar RT/RW, Domisili, Pengantar KTP/KK, Kematian, SKTM, Nikah/Kelahiran/Kepindahan, Keterangan Usaha
- 🖋️ **Editor A4** ala Word online untuk templat dan surat manual, dengan tombol sisip variabel `{{nama}}`, `{{alamat}}`, `{{nomor_surat}}`, dll. serta kolom tambahan per templat
- 🏡 **Pengajuan warga** dari rumah, lengkap dengan kolom tambahan sesuai jenis surat dan riwayat proses
- ✅ **Persetujuan berjenjang**: Sekretaris meneruskan → Wakil Ketua memeriksa (*checker*) → Ketua menandatangani (*signer*); setiap tahap bisa menolak dengan catatan
- 🔏 **Tanda tangan digital (QR) atau basah**, dipilih Sekretaris dan bisa diubah Ketua saat menandatangani
- 🖨️ **Cetak A4 berkop** (logo kiri & kanan, moto di kaki surat), tanda air *DRAF* sebelum disetujui
- 🔎 **Cek keaslian publik**: halaman verifikasi QR dan pop-up **Cek Surat** di beranda (pindai kamera, foto QR, atau nomor + tanggal), data pemohon disensor (`Bu** Sa****`)
- 🗂️ **Arsip surat** dengan pencarian dan saringan status

| Menu | Siapa |
|---|---|
| Surat Pengantar | Warga mengajukan; pengurus memantau |
| Persetujuan Surat | Pengurus (tombol muncul sesuai wewenang tahap) |
| Buat Surat, Templat Surat | Sekretaris, Wakil Sekretaris, admin |
| Arsip Surat | Pengurus |

> Agar QR bisa dipindai warga, isi **Pengaturan Situs → Kop surat → Alamat publik website** (jangan `localhost`). Kamera browser butuh **https** atau **localhost**.

## 💰 Fitur Tahap 3 — Keuangan

- 🏷️ **Jenis tagihan**: IPL dan Kas RT bawaan (nominal contoh, bisa diubah), Bendahara bebas menambah jenis lain, menandai wajib/opsional, dan menentukan alokasi kas
- 📤 **Terbitkan tagihan wajib** ke semua keluarga dalam satu klik per periode; tagihan ganda dicegah di tingkat database
- 🧾 **Tagihan lain** untuk semua keluarga atau satu keluarga, dengan nominal sendiri
- 📱 **Dasbor warga fokus tagihan**: daftar yang harus dibayar, info rekening/e-wallet, unggah bukti (foto/PDF)
- ✅ **Verifikasi Bendahara**: terima → lunas + **struk digital** + pemasukan kas otomatis; tolak dengan alasan; tandai lunas tunai
- 🔒 **Bukti bayar disimpan privat** (`storage/app/private`), hanya bisa dibuka keluarga pemilik dan pengurus
- 🏦 **Kas RT**: saldo, laporan bulanan, dana per alokasi, mutasi; warga hanya melihat transaksi yang disetujui
- ⚖️ **Pengeluaran berjenjang**: Bendahara memposting → Wakil memeriksa → Ketua menyetujui; baru masuk saldo setelah disetujui
- 🖨️ **Laporan Keuangan** per periode: saldo awal–akhir, rekap per kategori, rincian masuk/keluar, cetak berkop dengan tanda tangan Ketua & Bendahara
- 📊 **Grafik arus kas** bulanan di dasbor pengurus

> Pembayaran masih **manual** (unggah bukti). Integrasi payment gateway bisa ditambahkan di `App\Services\BillService::accept()`.

## 📣 Fitur Tahap 4 — Komunitas

- 📢 **Pengumuman** per kategori (kegiatan, kerja bakti, siskamling, keamanan); dibuat Ketua, Wakil, Sekretaris; semua akun langsung mendapat notifikasi
- 🛠️ **Aduan & aspirasi** dengan foto (disimpan privat): warga mengirim, pengurus mengubah status Baru → Diproses → Selesai dan memberi tanggapan; keluarga pelapor diberi tahu
- 🔔 **Notifikasi per peran**: lonceng di bilah atas + halaman notifikasi + notifikasi browser (opsional) dan bunyi. Contoh: Wakil diberi tahu saat surat/pengeluaran perlu diperiksa, Ketua saat perlu ditandatangani/disetujui, Bendahara saat ada bukti bayar, warga saat tagihan terbit atau surat selesai
- 🚨 **Tombol darurat** (kebakaran, pencurian, medis, dll.): layar merah + sirene di perangkat **semua pengurus dan warga** yang sedang membuka portal, lengkap dengan nama dan alamat rumah pengirim; pengurus menandai "situasi aman"; dibatasi 1 sinyal per 2 menit per akun
- 💬 **Chat pop-up**: grup Warga, grup Pengurus, dan pesan pribadi; warga bisa menghubungi pengurus secara pribadi, jumlah pesan belum dibaca tampil di tombol chat
- 📊 Dasbor menampilkan pengumuman terbaru, aduan keluarga (warga), dan jumlah aduan baru (pengurus)

> Notifikasi, darurat, dan chat memakai **polling** ringan (15 detik; chat 4 detik saat dibuka), jadi berjalan di XAMPP biasa tanpa server websocket.

## 📹 Fitur Tahap 5 — CCTV

- 🗺️ **Kelola CCTV (admin)**: nama, merek, area/kelompok, titik pemasangan, urutan tampil, aktif/nonaktif, dan siapa yang boleh melihat (semua akun atau hanya pengurus)
- 🔌 **Konektivitas segala merek**:

| Jenis | Cocok untuk | Cara kerja |
|---|---|---|
| **RTSP** | Hikvision, Dahua, Imou, EZVIZ, Tapo, Reolink, Uniview, NVR/DVR umum | lewat gateway **go2rtc** atau **MediaMTX** (WebRTC, jeda rendah); sandi kamera tidak pernah sampai ke browser |
| **HLS (.m3u8)** | NVR/cloud yang menyediakan HLS, gateway | diputar dengan hls.js / HLS bawaan Safari |
| **MJPEG** | kamera IP lama, Axis, ESP32-CAM | aliran gambar langsung |
| **Snapshot** | hampir semua kamera IP (`/ISAPI/.../picture`, `/cgi-bin/snapshot.cgi`) | diperbarui tiap N detik; bisa diambil lewat server portal (Basic & Digest auth) sehingga sandi tersembunyi |
| **Embed** | layanan cloud pihak ketiga (Ivideon, Angelcam, RTSP.me, dll.), halaman web merek | tempel tautan atau kode `<iframe>` |
| **YouTube Live** | siaran publik | tautan video/siaran atau ID kanal (selalu memutar siaran terbaru kanal itu) |

- 📺 **Layar pantau semua peran**: kisi 1–4 kolom, filter per area, jam berjalan, layar penuh, tombol "coba lagi"; pemutar hanya aktif saat terlihat di layar dan berhenti saat tab disembunyikan (hemat kuota)
- ✅ **Tes koneksi** per kamera atau sekaligus: cek port RTSP + gateway, jawaban HTTP, dan jenis isi (gambar/MJPEG)
- 🔔 **Pemantauan otomatis** `php artisan rt:cek-cctv` tiap 5 menit: admin, Seksi & Tim Keamanan diberi tahu saat kamera putus dan saat pulih
- 📄 **Unduh konfigurasi** `go2rtc.yaml` / MediaMTX `paths` yang dibuat otomatis dari daftar kamera RTSP
- 🔐 URL sumber disimpan **terenkripsi** (memakai `APP_KEY`), di layar admin sandi ditampilkan `****`

### Menyiapkan gateway RTSP (go2rtc, Windows)
1. Unduh `go2rtc_win64.zip` dari https://github.com/AlexxIT/go2rtc/releases, ekstrak ke mis. `C:\go2rtc`.
2. Tambahkan kamera RTSP di **Kelola CCTV**, lalu klik **go2rtc.yaml** dan simpan di folder yang sama.
3. Jalankan `go2rtc.exe` (biarkan menyala; bisa dijadikan layanan Windows dengan NSSM).
4. Di **Kelola CCTV → Gateway**, isi alamat yang bisa dibuka warga, mis. `http://192.168.1.10:1984`, lalu **Tes**.

> Warga di luar jaringan perumahan perlu akses ke gateway: gunakan port forwarding, Tailscale, atau Cloudflare Tunnel. Jika portal memakai **https**, gateway juga harus **https** (browser memblokir konten http di halaman https).

### Menjalankan pemantauan otomatis
Jadwal Laravel perlu dijalankan tiap menit:
- Linux: cron `* * * * * cd /path/portal-warga && php artisan schedule:run >> /dev/null 2>&1`
- Windows: Task Scheduler, aksi `php C:\xampp\htdocs\portal-warga\artisan schedule:run`, ulangi tiap 1 menit
- Atau jalankan manual/sementara: `php artisan schedule:work`

## 🔁 Pindah Data dari Versi PHP

Database lama (`db_rt`) cukup dibaca, tidak diubah:

```bash
php artisan migrate:fresh --seed
php artisan rt:pindah-data --database=db_rt --username=root --password= --uploads="C:/xampp/htdocs/web-rt/app/uploads"
```

| Opsi | Bawaan | Keterangan |
|---|---|---|
| `--host`, `--port` | `127.0.0.1`, `3306` | server database lama |
| `--database` | `db_rt` | nama database lama |
| `--username`, `--password` | `root`, kosong | akun database lama |
| `--uploads` | – | folder `app/uploads` versi lama: logo, foto, dan bukti bayar ikut disalin |
| `--force` | – | tetap jalan walau database baru sudah berisi data warga |

Yang dipindah: keluarga, akun (**kata sandi lama tetap berlaku**), jabatan, pengaturan situs + gambar, templat surat, surat terbit (kode QR lama tetap bisa diverifikasi), pengajuan surat, jenis tagihan, tagihan + bukti bayar, kas, pengumuman, aduan. Riwayat alur diubah ke format baru. Chat, notifikasi, dan riwayat darurat tidak dipindah. Semua berjalan dalam satu transaksi: jika ada galat, tidak ada yang tersimpan.

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

# 4. Buat tabel + akun admin awal + 7 templat surat + jenis tagihan IPL & Kas RT
php artisan migrate --seed

# (opsional) data contoh: 6 keluarga, akun pengurus, tagihan bulan ini, dan kas
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

| Peran / jabatan | Wewenang |
|---|---|
| 🛠️ Administrator | Semua wewenang, akun & jabatan, impor, pengaturan, tema |
| 👑 Ketua RT | Menandatangani surat, menyetujui pengeluaran kas, pengumuman, menangani aduan |
| 🔍 Wakil Ketua | Memeriksa surat dan pengeluaran kas, pengumuman, menangani aduan |
| 📝 Sekretaris / Wakil Sekretaris | Data warga, membuat surat (manual/templat), meneruskan pengajuan, templat, pengumuman, aduan |
| 💼 Bendahara | Jenis tagihan, menerbitkan tagihan, verifikasi bukti bayar, memposting kas |
| 🧹 Seksi & tim | Akses pengurus: dasbor, data warga (lihat), grup chat pengurus |
| 🏡 Warga | Membayar tagihan, mengajukan surat, mengirim aduan, membaca pengumuman, chat dengan pengurus |
| 🚨 Semua akun | Tombol darurat, notifikasi, chat, menonton CCTV |
| 📹 Administrator (CCTV) | Menambah, menempatkan, menamai, dan menghubungkan kamera; gateway; tes koneksi |

---

## 🗄️ Rancangan Database (baru)

| Tabel | Isi |
|---|---|
| `users` | akun: `username`, `role` (admin/pengurus/warga), `position` (jabatan), `household_id`, `photo`, `must_change_password` |
| `households` | kepala keluarga: KK, NIK, lahir, `block`, `house_number`, `members_count`, `residency_status`, kontak darurat |
| `settings` | pengaturan situs kunci-nilai (nama, logo, tema, teks beranda, kop surat) |
| `letter_templates` | templat surat: isi HTML + kolom tambahan |
| `letter_requests` | pengajuan surat warga + riwayat persetujuan |
| `letters` | draf & surat terbit: nomor, isi, TTD QR/basah, token verifikasi, riwayat |
| `bill_types` | jenis tagihan: nominal bawaan, alokasi kas, wajib/opsional |
| `bills` | tagihan per keluarga per periode, bukti bayar, struk |
| `cash_transactions` | buku kas: pemasukan/pengeluaran, status persetujuan, riwayat |
| `announcements`, `complaints` | pengumuman dan aduan warga |
| `notifications` | notifikasi bawaan Laravel (database) |
| `emergencies` | sinyal darurat aktif & riwayat |
| `chat_messages`, `chat_reads` | pesan chat per ruang dan penanda baca |
| `cameras` | kamera CCTV: nama, area, titik, jenis koneksi, sumber terenkripsi, nama aliran gateway, status koneksi terakhir |
| `sessions`, `cache`, `jobs` | bawaan Laravel |

> Database versi PHP **tidak langsung kompatibel**; pindahkan dengan `php artisan rt:pindah-data` (lihat di atas).

---

## 📁 Struktur Penting

```
app/
├── Http/Controllers/         Beranda, Login, Dasbor, Warga, Profil, Struktur
│   ├── Letters/              Pengajuan, Persetujuan, Templat, Buat/Arsip/Cetak, Verifikasi
│   ├── Finance/              Tagihan, Jenis Tagihan, Kas & Persetujuan Kas, Laporan
│   ├── Community/            Pengumuman, Aduan, Notifikasi, Darurat, Chat
│   ├── CctvController.php    layar pantau + snapshot lewat server
│   └── Admin/                Akun, Impor, Pengaturan, Tema, Kamera (CCTV)
├── Http/Middleware/EnsurePasswordChanged.php
├── Models/                   User, Household, Setting, Letter*, Bill*, CashTransaction,
│                             Announcement, Complaint, Emergency, ChatMessage, Camera
├── Services/                 PositionService, HouseholdImporter, SpreadsheetReader,
│                             LetterRenderer, LetterWorkflow, BillService, CashWorkflow, CameraProbe
├── Console/Commands/         PindahData (rt:pindah-data), CekCctv (rt:cek-cctv)
├── Notifications/            PortalNotice
├── Support/                  Board (profil Ketua/Wakil), Uploads, Notify
└── helpers.php               setting(), rt_name(), rt_asset(), upload_url(), ...
config/rt.php                 jabatan, wewenang, tema, nilai bawaan situs
resources/views/              Blade + Bootstrap 5
public/js/app.js              lonceng, darurat, chat pop-up
public/js/cctv.js             pemutar CCTV (HLS, MJPEG, snapshot, iframe)
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

- Grafik dasbor memakai Chart.js dari CDN sampai berkas `public/vendor/chart/chart.umd.min.js` ditambahkan; pemutar HLS memakai hls.js dari CDN sampai `public/vendor/hls/hls.min.js` ditambahkan; pemindai kamera Cek Surat memakai html5-qrcode dari CDN sampai `public/vendor/html5-qrcode/html5-qrcode.min.js` ditambahkan (tanpa itu, dipakai pembaca QR bawaan browser bila tersedia).
- Kode ini ditulis tanpa bisa dijalankan di lingkungan pembuatnya; uji pertama dilakukan di server Anda.
