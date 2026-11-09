<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Portal Warga RT
|--------------------------------------------------------------------------
| Jabatan, wewenang per jabatan, tema, dan nilai bawaan pengaturan situs.
| Nilai pengaturan yang diubah admin disimpan di tabel `settings` dan
| selalu didahulukan daripada nilai bawaan di sini.
*/

return [

    // Kunci jabatan => label. Urutan ini juga dipakai untuk mengurutkan daftar akun.
    'positions' => [
        'ketua' => 'Ketua RT',
        'wakil' => 'Wakil Ketua RT',
        'sekretaris' => 'Sekretaris',
        'wakil_sekretaris' => 'Wakil Sekretaris',
        'bendahara' => 'Bendahara',
        'seksi_pembangunan' => 'Seksi Pembangunan & Kesejahteraan Sosial',
        'seksi_keamanan' => 'Seksi Keamanan',
        'tim_keamanan' => 'Tim Keamanan',
        'seksi_kebersihan' => 'Seksi Kebersihan & Lingkungan Hidup',
        'tim_kebersihan' => 'Tim Kebersihan',
        'seksi_pkk' => 'Seksi Pemberdayaan Perempuan & Tata Laksana Rumah Tangga',
        'seksi_pemuda' => 'Seksi Pemuda, Olahraga & Seni Budaya',
        'warga' => 'Warga',
    ],

    // Jabatan yang hanya boleh dipegang satu orang. Memberi jabatan ini ke akun lain
    // otomatis melepasnya dari pemegang lama.
    'single_positions' => ['ketua', 'wakil', 'sekretaris', 'wakil_sekretaris', 'bendahara'],

    // Jabatan inti yang otomatis mendapat akses pengurus.
    'core_positions' => ['ketua', 'wakil', 'sekretaris', 'wakil_sekretaris', 'bendahara'],

    // Wewenang => jabatan yang memilikinya. Admin selalu punya semua wewenang.
    // Didaftarkan sebagai Gate di AppServiceProvider, dipakai dengan @can / ->can().
    'capabilities' => [
        // Tahap 1
        'kelola-warga' => ['sekretaris', 'wakil_sekretaris'],
        // Tahap berikutnya (sudah disiapkan agar alur tetap sama dengan versi PHP)
        'buat-surat' => ['sekretaris', 'wakil_sekretaris'],
        'teruskan-surat' => ['sekretaris', 'wakil_sekretaris'],
        'periksa-surat' => ['wakil'],
        'tandatangani-surat' => ['ketua'],
        'kelola-tagihan' => ['bendahara'],
        'verifikasi-bayar' => ['bendahara'],
        'catat-kas' => ['bendahara'],
        'periksa-kas' => ['wakil'],
        'setujui-kas' => ['ketua'],
        'kelola-pengumuman' => ['ketua', 'wakil', 'sekretaris', 'wakil_sekretaris'],
        'tangani-aduan' => ['ketua', 'wakil', 'sekretaris', 'wakil_sekretaris'],
    ],

    'roles' => [
        'admin' => 'Administrator',
        'pengurus' => 'Pengurus RT',
        'warga' => 'Warga',
    ],

    'themes' => [
        'teal' => ['name' => 'Teal Segar', 'description' => 'Bersih dan ringan, cocok untuk perumahan modern.', 'swatch' => ['#0E7C86', '#E6F4F5', '#F2B705', '#F5F7FA']],
        'biru' => ['name' => 'Biru Resmi', 'description' => 'Formal dan tepercaya, sidebar gelap ala kantor kelurahan.', 'swatch' => ['#1D4E89', '#E7EFF9', '#E3B23C', '#F4F6FA']],
        'terakota' => ['name' => 'Hangat Terakota', 'description' => 'Hangat dan guyub, judul berhuruf serif.', 'swatch' => ['#B5532F', '#FBEDE6', '#E9B949', '#FBF7F2']],
    ],

    'feature_icons' => [
        'bi-bank', 'bi-envelope-paper', 'bi-megaphone', 'bi-wallet2', 'bi-people', 'bi-chat-left-text',
        'bi-shield-check', 'bi-tree', 'bi-heart', 'bi-calendar-event', 'bi-house-heart', 'bi-trophy',
        'bi-lightning', 'bi-droplet', 'bi-camera', 'bi-geo-alt', 'bi-bicycle', 'bi-basket',
    ],

    // Metode bayar yang bisa dipilih warga saat mengunggah bukti (pembayaran masih manual).
    'payment_methods' => ['Transfer BCA', 'Transfer Mandiri', 'Transfer BRI', 'Transfer BNI', 'Transfer bank lain', 'GoPay', 'OVO', 'DANA', 'ShopeePay', 'QRIS'],

    // Saran alokasi dana kas (bebas diketik selain ini).
    'cash_allocations' => ['Kas umum', 'IPL', 'Kebersihan', 'Keamanan', 'Pembangunan saung', 'Playground', 'Kegiatan 17 Agustus'],

    'religions' => ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'],

    // Kata sandi bawaan akun hasil impor Excel (pengguna diminta menggantinya saat masuk).
    'import_password' => env('RT_IMPORT_PASSWORD', 'warga123'),

    // Nilai bawaan pengaturan situs.
    'defaults' => [
        'site_name' => 'Portal Warga',
        'brand_sub' => '',
        'theme' => 'teal',
        'logo' => '',
        'logo_right' => '',
        'favicon' => '',
        'rt_number' => '05',
        'rw_number' => '02',
        'village' => 'Kelurahan Contoh',
        'district' => 'Kecamatan Contoh',
        'city' => 'Kota Surabaya',
        'secretariat_address' => 'Jl. Mawar No. 1',
        'motto' => 'Rukun, Aman, dan Guyub',
        'hero_badge' => '',
        'hero_title' => 'Lingkungan yang tertata, urusan warga jadi lebih ringan.',
        'hero_lead' => 'Bayar iuran, ajukan surat pengantar, baca pengumuman, dan lihat ke mana dana kas dipakai, semuanya dari rumah.',
        'hero_button_primary' => 'Masuk sebagai warga',
        'hero_button_secondary' => 'Kenali pengurus',
        'hero_image' => '',
        'show_stats' => '1',
        'board_title' => '',
        'board_subtitle' => 'Siap melayani dan menampung aspirasi warga.',
        'profile_source' => 'account',
        'chair_name' => 'Nama Ketua RT',
        'chair_term' => 'Periode 2024–2027',
        'chair_message' => 'Selamat datang di portal warga. Mari bersama menjaga lingkungan yang aman, bersih, dan guyub.',
        'chair_photo' => '',
        'vice_name' => 'Nama Wakil Ketua RT',
        'vice_term' => 'Periode 2024–2027',
        'vice_message' => 'Kami siap membantu kebutuhan administrasi dan menampung aspirasi warga.',
        'vice_photo' => '',
        'features' => '',
        'footer_left' => '',
        'footer_right' => 'Surat resmi dapat diverifikasi lewat kode QR pada dokumen.',
        // Kop surat (kosong = dibuat otomatis dari data wilayah)
        'letterhead_line1' => '',
        'letterhead_line2' => '',
        'letterhead_line3' => '',
        'letterhead_line4' => '',
        // Info rekening / e-wallet tujuan transfer, tampil di dasbor warga
        'payment_info' => "Transfer: BCA 1234567890 a.n. Bendahara RT\nE-wallet: DANA / OVO 0812-0000-0000",
        // Alamat publik website untuk QR verifikasi (kosong = APP_URL). Isi jika website diakses lewat domain lain.
        'cctv_gateway' => 'go2rtc',   // go2rtc | mediamtx: server perantara untuk kamera RTSP
        'cctv_gateway_url' => '',     // alamat gateway yang bisa dibuka browser warga, mis. http://192.168.1.10:1984
        'public_url' => '',
    ],

    'default_features' => [
        ['icon' => 'bi-bank', 'title' => 'Kas RT yang terbuka', 'text' => 'Pemasukan, pengeluaran, dan dana khusus seperti saung atau playground bisa dilihat semua warga.'],
        ['icon' => 'bi-envelope-paper', 'title' => 'Surat pengantar mandiri', 'text' => 'Ajukan surat domisili, KTP/KK, SKTM, hingga keterangan usaha tanpa datang ke rumah pengurus.'],
        ['icon' => 'bi-megaphone', 'title' => 'Pengumuman & siskamling', 'text' => 'Jadwal kerja bakti, piket ronda, dan laporan keamanan dalam satu papan.'],
        ['icon' => 'bi-wallet2', 'title' => 'Bayar iuran online', 'text' => 'Transfer bank atau e-wallet, unggah bukti, dan dapatkan struk digital.'],
        ['icon' => 'bi-people', 'title' => 'Data warga terpusat', 'text' => 'Kepala keluarga, anggota, status hunian, dan kontak darurat tercatat rapi.'],
        ['icon' => 'bi-chat-left-text', 'title' => 'Aduan & aspirasi', 'text' => 'Laporkan lampu jalan mati atau jalan rusak, lalu pantau statusnya.'],
    ],

    // Aset tampilan: berkas lokal di public/ (mode offline) dengan cadangan CDN.
    'assets' => [
        'fonts.css' => ['vendor/font/fonts.css', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&display=swap'],
        'bootstrap.css' => ['vendor/bootstrap-5.3.8-dist/css/bootstrap.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'],
        'bootstrap.js' => ['vendor/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js'],
        'icons.css' => ['vendor/bootstrap-icons-1.13.2/bootstrap-icons.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css'],
        'chart.js' => ['vendor/chart/chart.umd.min.js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js'],
        'qrcode.js' => ['vendor/qrcode/qrcode.min.js', 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js'],
        'hls.js' => ['vendor/hls/hls.min.js', 'https://cdn.jsdelivr.net/npm/hls.js@1.5.20/dist/hls.min.js'],
        'html5-qrcode.js' => ['vendor/html5-qrcode/html5-qrcode.min.js', 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js'],
    ],
];
