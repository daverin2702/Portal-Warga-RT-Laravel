<?php

use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
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
Route::get('/verifikasi/{token}', [VerificationController::class, 'show'])->where('token', '[A-Za-z0-9]{32}')->name('letters.verify');
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
