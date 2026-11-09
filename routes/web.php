<?php

use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Community\AnnouncementController;
use App\Http\Controllers\Community\ChatController;
use App\Http\Controllers\Community\ComplaintController;
use App\Http\Controllers\Community\EmergencyController;
use App\Http\Controllers\Community\NotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\BillController;
use App\Http\Controllers\Finance\BillTypeController;
use App\Http\Controllers\Finance\CashController;
use App\Http\Controllers\Finance\ReportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\Letters\ApprovalController;
use App\Http\Controllers\Letters\LetterController;
use App\Http\Controllers\Letters\LetterRequestController;
use App\Http\Controllers\Letters\LetterTemplateController;
use App\Http\Controllers\Letters\VerificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Verifikasi keaslian surat (publik)
Route::get('/verifikasi/{token}', [VerificationController::class, 'show'])->where('token', '[A-Za-z0-9]{24,32}')->name('letters.verify');
Route::post('/cek-surat', [VerificationController::class, 'check'])->middleware('throttle:cek-surat')->name('letters.check');

// Masuk: satu halaman per peran (warga, pengurus, admin)
Route::middleware('guest')->group(function () {
    Route::get('/masuk/{peran?}', [LoginController::class, 'show'])->whereIn('peran', ['warga', 'pengurus', 'admin'])->name('login');
    Route::post('/masuk/{peran?}', [LoginController::class, 'store'])->whereIn('peran', ['warga', 'pengurus', 'admin'])
        ->middleware('throttle:login')->name('login.store');
});

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::post('/keluar', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dasbor', DashboardController::class)->name('dashboard');
    Route::get('/struktur-pengurus', OrganizationController::class)->name('organization');

    // Profil Saya (tetap bisa dibuka meski sandi bawaan belum diganti; lihat EnsurePasswordChanged)
    Route::get('/profil-saya', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil-saya/akun', [ProfileController::class, 'updateAccount'])->name('profile.account');
    Route::put('/profil-saya/sandi', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/profil-saya/keluarga', [ProfileController::class, 'updateHousehold'])->name('profile.household');

    // Surat Pengantar: warga mengajukan, pengurus memantau
    Route::get('/surat', [LetterRequestController::class, 'index'])->name('letters.requests.index');
    Route::post('/surat', [LetterRequestController::class, 'store'])->name('letters.requests.store');
    Route::get('/surat/{letter}/cetak', [LetterController::class, 'print'])->name('letters.print');

    Route::middleware('can:pengurus')->group(function () {
        Route::get('/persetujuan-surat', [ApprovalController::class, 'index'])->name('letters.approvals.index');
        Route::post('/persetujuan-surat/pengajuan/{letterRequest}', [ApprovalController::class, 'request'])->name('letters.approvals.request');
        Route::post('/persetujuan-surat/draf/{letter}', [ApprovalController::class, 'draft'])->name('letters.approvals.draft');
        Route::get('/arsip-surat', [LetterController::class, 'index'])->name('letters.archive');

        // Sekretaris / Wakil Sekretaris (dan admin)
        Route::middleware('can:buat-surat')->group(function () {
            Route::get('/buat-surat', [LetterController::class, 'create'])->name('letters.create');
            Route::post('/buat-surat', [LetterController::class, 'store'])->name('letters.store');
            Route::delete('/arsip-surat/{letter}', [LetterController::class, 'destroy'])->name('letters.destroy');

            Route::get('/templat-surat', [LetterTemplateController::class, 'index'])->name('letters.templates.index');
            Route::get('/templat-surat/tambah', [LetterTemplateController::class, 'create'])->name('letters.templates.create');
            Route::post('/templat-surat', [LetterTemplateController::class, 'store'])->name('letters.templates.store');
            Route::get('/templat-surat/{template}/ubah', [LetterTemplateController::class, 'edit'])->name('letters.templates.edit');
            Route::put('/templat-surat/{template}', [LetterTemplateController::class, 'update'])->name('letters.templates.update');
            Route::post('/templat-surat/{template}/duplikat', [LetterTemplateController::class, 'duplicate'])->name('letters.templates.duplicate');
            Route::delete('/templat-surat/{template}', [LetterTemplateController::class, 'destroy'])->name('letters.templates.destroy');
        });
    });

    // Komunitas: pengumuman, aduan, notifikasi, darurat, chat
    Route::get('/pengumuman', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::middleware('can:kelola-pengumuman')->group(function () {
        Route::post('/pengumuman', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::delete('/pengumuman/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    });
    Route::get('/aduan', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::post('/aduan', [ComplaintController::class, 'store'])->name('complaints.store');
    Route::get('/aduan/{complaint}/foto', [ComplaintController::class, 'photo'])->name('complaints.photo');
    Route::put('/aduan/{complaint}', [ComplaintController::class, 'update'])->middleware('can:tangani-aduan')->name('complaints.update');

    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifikasi/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
    Route::post('/notifikasi/baca', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/darurat', [EmergencyController::class, 'store'])->middleware('throttle:10,1')->name('emergencies.store');
    Route::post('/darurat/{emergency}/aman', [EmergencyController::class, 'resolve'])->name('emergencies.resolve');
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{room}', [ChatController::class, 'show'])->where('room', 'umum|pengurus|dm-\d+-\d+')->name('chat.show');
    Route::post('/chat/{room}', [ChatController::class, 'store'])->where('room', 'umum|pengurus|dm-\d+-\d+')->middleware('throttle:30,1')->name('chat.store');

    // Keuangan: tagihan, kas, laporan
    Route::get('/tagihan', [BillController::class, 'index'])->name('finance.bills.index');
    Route::post('/tagihan/{bill}/bukti', [BillController::class, 'uploadProof'])->name('finance.bills.proof.store');
    Route::get('/tagihan/{bill}/bukti', [BillController::class, 'proof'])->name('finance.bills.proof');
    Route::get('/tagihan/{bill}/struk', [BillController::class, 'receipt'])->name('finance.bills.receipt');
    Route::get('/kas', [CashController::class, 'index'])->name('finance.cash.index');
    Route::get('/laporan-keuangan', ReportController::class)->name('finance.report');

    Route::middleware('can:kelola-tagihan')->group(function () {
        Route::post('/tagihan/wajib', [BillController::class, 'issueMandatory'])->name('finance.bills.mandatory');
        Route::post('/tagihan', [BillController::class, 'store'])->name('finance.bills.store');
        Route::delete('/tagihan/{bill}', [BillController::class, 'destroy'])->name('finance.bills.destroy');
        Route::get('/jenis-tagihan', [BillTypeController::class, 'index'])->name('finance.types.index');
        Route::post('/jenis-tagihan', [BillTypeController::class, 'store'])->name('finance.types.store');
        Route::put('/jenis-tagihan/{type}', [BillTypeController::class, 'update'])->name('finance.types.update');
        Route::patch('/jenis-tagihan/{type}/aktif', [BillTypeController::class, 'toggle'])->name('finance.types.toggle');
    });
    Route::middleware('can:verifikasi-bayar')->group(function () {
        Route::post('/tagihan/{bill}/terima', [BillController::class, 'accept'])->name('finance.bills.accept');
        Route::post('/tagihan/{bill}/tolak', [BillController::class, 'reject'])->name('finance.bills.reject');
        Route::post('/tagihan/{bill}/tunai', [BillController::class, 'cash'])->name('finance.bills.cash');
    });
    Route::middleware('can:catat-kas')->group(function () {
        Route::post('/kas', [CashController::class, 'store'])->name('finance.cash.store');
        Route::delete('/kas/{cash}', [CashController::class, 'destroy'])->name('finance.cash.destroy');
    });
    Route::middleware('can:pengurus')->group(function () {
        Route::get('/persetujuan-kas', [CashController::class, 'approvals'])->name('finance.cash.approvals');
        Route::post('/persetujuan-kas/{cash}', [CashController::class, 'approve'])->name('finance.cash.approve');
    });

    // Data warga: semua pengurus bisa melihat, hanya Sekretaris/Wakil Sekretaris (dan admin) yang mengubah
    Route::middleware('can:pengurus')->group(function () {
        Route::get('/warga', [HouseholdController::class, 'index'])->name('households.index');
        Route::middleware('can:kelola-warga')->group(function () {
            Route::get('/warga/tambah', [HouseholdController::class, 'create'])->name('households.create');
            Route::post('/warga', [HouseholdController::class, 'store'])->name('households.store');
            Route::get('/warga/{household}/ubah', [HouseholdController::class, 'edit'])->name('households.edit');
            Route::put('/warga/{household}', [HouseholdController::class, 'update'])->name('households.update');
            Route::delete('/warga/{household}', [HouseholdController::class, 'destroy'])->name('households.destroy');
        });
    });

    // Menu administrator
    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/akun', [UserController::class, 'index'])->name('users.index');
        Route::get('/akun/tambah', [UserController::class, 'create'])->name('users.create');
        Route::post('/akun', [UserController::class, 'store'])->name('users.store');
        Route::get('/akun/{user}/ubah', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/akun/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/akun/{user}/jabatan', [UserController::class, 'position'])->name('users.position');
        Route::delete('/akun/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/impor-warga', [ImportController::class, 'create'])->name('import.create');
        Route::post('/impor-warga', [ImportController::class, 'store'])->name('import.store');
        Route::get('/impor-warga/templat', [ImportController::class, 'template'])->name('import.template');

        Route::get('/pengaturan', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/pengaturan', [SettingController::class, 'update'])->name('settings.update');

        Route::get('/tema', [ThemeController::class, 'index'])->name('themes.index');
        Route::put('/tema', [ThemeController::class, 'update'])->name('themes.update');
    });
});
