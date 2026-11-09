@php
    $u = auth()->user();
    $menu = [
        ['Dasbor', 'dashboard', 'bi-grid-1x2', true],
        ['Struktur Pengurus', 'organization', 'bi-diagram-3', true],
        ['Profil Saya', 'profile.edit', 'bi-person-circle', true],
        ['#', 'Layanan', null, true],
        ['Surat Pengantar', 'letters.requests.index', 'bi-envelope-paper', true],
        ['Tagihan & Pembayaran', 'finance.bills.index', 'bi-wallet2', true],
        ['Kas RT', 'finance.cash.index', 'bi-bank', true],
        ['Laporan Keuangan', 'finance.report', 'bi-file-earmark-bar-graph', true],
        ['Pengumuman', 'announcements.index', 'bi-megaphone', true],
        ['Aduan & Aspirasi', 'complaints.index', 'bi-chat-left-text', true],
        ['#', 'Kepengurusan', null, $u->can('pengurus')],
        ['Persetujuan Surat', 'letters.approvals.index', 'bi-check2-square', $u->can('pengurus')],
        ['Buat Surat', 'letters.create', 'bi-pencil-square', $u->can('buat-surat')],
        ['Templat Surat', 'letters.templates.index', 'bi-file-earmark-richtext', $u->can('buat-surat')],
        ['Arsip Surat', 'letters.archive', 'bi-archive', $u->can('pengurus')],
        ['Persetujuan Kas', 'finance.cash.approvals', 'bi-cash-coin', $u->can('pengurus')],
        ['Jenis Tagihan', 'finance.types.index', 'bi-tags', $u->can('kelola-tagihan')],
        ['Data Warga', 'households.index', 'bi-people', $u->can('pengurus')],
        ['#', 'Administrator', null, $u->isAdmin()],
        ['Akun Pengguna', 'admin.users.index', 'bi-person-lock', $u->isAdmin()],
        ['Impor Data Warga', 'admin.import.create', 'bi-file-earmark-excel', $u->isAdmin()],
        ['Pengaturan Situs', 'admin.settings.edit', 'bi-gear', $u->isAdmin()],
        ['Tema Website', 'admin.themes.index', 'bi-palette', $u->isAdmin()],
    ];
    $logo = upload_url(setting('logo'));
@endphp
<!doctype html>
<html lang="id" data-theme="{{ active_theme() }}">
<head>@include('partials.head')</head>
<body>
<div class="d-flex">
    <aside class="offcanvas-md offcanvas-start sidebar no-print" id="sb" tabindex="-1">
        <div class="brand">
            @if ($logo)<img src="{{ $logo }}" alt="">@else<div class="logo-ph">RT</div>@endif
            <div><div class="fw-bold lh-1">{{ setting('site_name') }}</div><div class="small text-secondary">{{ rt_brand_sub() }}</div></div>
        </div>
        <nav class="nav flex-column">
            @foreach ($menu as [$label, $route, $icon, $show])
                @continue(! $show)
                @if ($label === '#')
                    <div class="nav-label">{{ $route }}</div>
                @else
                    @php($pola = str_ends_with($route, '.index') ? \Illuminate\Support\Str::beforeLast($route, '.').'.*' : $route)
                    <a class="nav-link {{ request()->routeIs($pola) ? 'active' : '' }}" href="{{ route($route) }}"><i class="bi {{ $icon }}"></i>{{ $label }}</a>
                @endif
            @endforeach
        </nav>
        <div class="me">
            <div class="d-flex align-items-center gap-2">
                @include('partials.avatar', ['url' => $u->photoUrl(), 'name' => $u->name, 'size' => 34])
                <div class="lh-sm"><div class="fw-semibold">{{ $u->name }}</div><div class="text-secondary">{{ $u->displayRole() }}</div></div>
            </div>
            <form method="post" action="{{ route('logout') }}" class="mt-2">@csrf<button class="btn btn-link p-0 text-decoration-none"><i class="bi bi-box-arrow-left me-1"></i>Keluar</button></form>
        </div>
    </aside>
    <main class="flex-grow-1 p-3 p-md-4" style="min-width:0">
        <div class="topbar no-print">
            <button class="btn btn-light d-md-none" data-bs-toggle="offcanvas" data-bs-target="#sb" aria-label="Menu"><i class="bi bi-list"></i></button>
            <div class="flex-grow-1"></div>
            <a href="{{ route('home') }}" class="btn btn-light btn-sm d-none d-lg-inline-block" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i>Halaman depan</a>
            <button class="btn btn-danger btn-panic" data-bs-toggle="modal" data-bs-target="#mPanic" title="Tombol darurat"><i class="bi bi-exclamation-octagon-fill"></i><span class="d-none d-sm-inline ms-1">Darurat</span></button>
            <button class="btn btn-light position-relative" id="btnChat" title="Chat" aria-label="Chat"><i class="bi bi-chat-dots"></i><span class="dot d-none" id="chatBadge"></span></button>
            <div class="dropdown">
                <button class="btn btn-light position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Notifikasi"><i class="bi bi-bell"></i><span class="dot d-none" id="bellBadge"></span></button>
                <div class="dropdown-menu dropdown-menu-end notif-menu p-0">
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom"><strong>Notifikasi</strong><button class="btn btn-link btn-sm p-0" id="bacaSemua">Tandai semua dibaca</button></div>
                    <div id="notifList"><div class="p-3 small text-secondary">Memuat...</div></div>
                    <div class="px-3 py-2 border-top small d-flex justify-content-between"><a href="{{ route('notifications.index') }}">Semua notifikasi</a><a href="#" id="izinBrowser">Aktifkan notifikasi browser</a></div>
                </div>
            </div>
        </div>
        <div class="mb-4 no-print">
            <h1 class="h3 fw-bold mb-1">{{ $title ?? '' }}</h1>
            @isset($subtitle)<div class="text-secondary">{{ $subtitle }}</div>@endisset
        </div>
        @include('partials.flash')
        @yield('content')
    </main>
</div>
<div class="modal fade no-print" id="mPanic" tabindex="-1" aria-labelledby="mPanicTitle"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:18px">
    <div class="modal-header border-0 pb-0"><h2 class="modal-title h5 fw-bold text-danger" id="mPanicTitle"><i class="bi bi-exclamation-octagon-fill me-1"></i>Kirim sinyal darurat</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
    <div class="modal-body">
        <p class="small text-secondary">Sinyal langsung muncul di layar semua pengurus dan warga yang sedang membuka website, lengkap dengan nama dan alamat Anda. Gunakan hanya untuk keadaan darurat sungguhan.</p>
        <div class="d-flex flex-wrap gap-2 mb-3">@foreach (\App\Models\Emergency::TYPES as $i => $jenis)<input type="radio" class="btn-check" name="jd" id="jd{{ $i }}" value="{{ $jenis }}" @checked($i === 0)><label class="btn btn-outline-danger btn-sm" for="jd{{ $i }}">{{ $jenis }}</label>@endforeach</div>
        <textarea class="form-control" id="pesanDarurat" rows="2" maxlength="300" placeholder="Keterangan singkat (opsional), mis. asap dari rumah sebelah" aria-label="Keterangan"></textarea>
        <div class="alert alert-light border small mt-3 mb-0">Untuk kebakaran, kecelakaan, atau kejahatan, hubungi juga <strong>112</strong>.</div>
    </div>
    <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-danger fw-bold" id="kirimDarurat">Kirim sinyal darurat</button></div>
</div></div></div>
<div class="sos-layer d-none" id="sosLayer" role="alertdialog" aria-live="assertive"><div class="sos-box"><div class="sos-ico"><i class="bi bi-exclamation-octagon-fill"></i></div><div id="sosIsi"></div></div></div>
<div class="chat-pop d-none no-print" id="chatPop" role="dialog" aria-label="Chat">
    <div class="chat-head"><button type="button" class="btn btn-sm text-white d-none" id="chatBack" aria-label="Kembali"><i class="bi bi-arrow-left"></i></button><strong class="flex-grow-1" id="chatTitle">Chat</strong><button type="button" class="btn btn-sm text-white" id="chatClose" aria-label="Tutup"><i class="bi bi-x-lg"></i></button></div>
    <div class="chat-body" id="chatBody"></div>
    <form class="chat-form d-none" id="chatForm"><input class="form-control" id="chatInput" maxlength="1000" placeholder="Tulis pesan..." autocomplete="off" aria-label="Pesan"><button class="btn btn-primary" aria-label="Kirim"><i class="bi bi-send-fill"></i></button></form>
</div>
<script src="{{ rt_asset('bootstrap.js') }}"></script>
<script>window.RT = @json(['csrf' => csrf_token(), 'me' => $u->id, 'board' => $u->isBoard(), 'url' => [
    'poll' => route('notifications.poll'), 'read' => route('notifications.read'), 'emergency' => route('emergencies.store'),
    'resolve' => url('/darurat'), 'chat' => route('chat.index'), 'home' => route('home'),
]]);</script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
@stack('scripts')
</body>
</html>
