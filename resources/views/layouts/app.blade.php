@php
    $u = auth()->user();
    $menu = [
        ['Dasbor', 'dashboard', 'bi-grid-1x2', true],
        ['Struktur Pengurus', 'organization', 'bi-diagram-3', true],
        ['Profil Saya', 'profile.edit', 'bi-person-circle', true],
        ['#', 'Layanan', null, true],
        ['Surat Pengantar', 'letters.requests.index', 'bi-envelope-paper', true],
        ['#', 'Kepengurusan', null, $u->can('pengurus')],
        ['Persetujuan Surat', 'letters.approvals.index', 'bi-check2-square', $u->can('pengurus')],
        ['Buat Surat', 'letters.create', 'bi-pencil-square', $u->can('buat-surat')],
        ['Templat Surat', 'letters.templates.index', 'bi-file-earmark-richtext', $u->can('buat-surat')],
        ['Arsip Surat', 'letters.archive', 'bi-archive', $u->can('pengurus')],
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
            <a href="{{ route('home') }}" class="btn btn-light btn-sm" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i>Halaman depan</a>
        </div>
        <div class="mb-4 no-print">
            <h1 class="h3 fw-bold mb-1">{{ $title ?? '' }}</h1>
            @isset($subtitle)<div class="text-secondary">{{ $subtitle }}</div>@endisset
        </div>
        @include('partials.flash')
        @yield('content')
    </main>
</div>
<script src="{{ rt_asset('bootstrap.js') }}"></script>
@stack('scripts')
</body>
</html>
