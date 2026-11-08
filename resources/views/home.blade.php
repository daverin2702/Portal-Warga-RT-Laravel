@extends('layouts.guest', ['title' => rt_name()])

@php($logo = upload_url(setting('logo')))
@php($heroImage = upload_url(setting('hero_image')))

@section('content')
<header class="topnav"><div class="container d-flex justify-content-between align-items-center py-2">
    <a href="{{ route('home') }}" class="d-flex align-items-center gap-2 text-decoration-none text-reset">
        @if ($logo)<img src="{{ $logo }}" width="40" height="40" class="rounded-3" style="object-fit:cover" alt="">@else<span class="plate" style="font-size:.8rem">RT</span>@endif
        <span class="lh-sm"><strong class="d-block">{{ setting('site_name') }}</strong><span class="text-secondary small">{{ rt_brand_sub() }}</span></span>
    </a>
    @auth
        <a href="{{ route('dashboard') }}" class="btn btn-primary">Buka dasbor</a>
    @else
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">Masuk</button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius:12px">
                <li><a class="dropdown-item" href="{{ route('login', 'warga') }}"><i class="bi bi-house-door me-2"></i>Warga</a></li>
                <li><a class="dropdown-item" href="{{ route('login', 'pengurus') }}"><i class="bi bi-person-badge me-2"></i>Pengurus RT</a></li>
                <li><a class="dropdown-item" href="{{ route('login', 'admin') }}"><i class="bi bi-shield-lock me-2"></i>Administrator</a></li>
            </ul>
        </div>
    @endauth
</div></header>

<section class="hero"><div class="container py-5">
    <div class="row align-items-center g-4 py-lg-4">
        <div class="{{ $heroImage ? 'col-lg-6' : 'col-lg-8' }}">
            <span class="plate mb-3">{{ setting('hero_badge') ?: rt_name() }}</span>
            <h1 class="display-5 fw-bold mt-3">{{ setting('hero_title') }}</h1>
            <p class="lead text-secondary">{{ setting('hero_lead') }}</p>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <a href="{{ route('login', 'warga') }}" class="btn btn-primary btn-lg px-4">{{ setting('hero_button_primary') }}</a>
                <a href="#pengurus" class="btn btn-light btn-lg px-4">{{ setting('hero_button_secondary') }}</a>
            </div>
            <div class="text-secondary small mt-3"><i class="bi bi-geo-alt me-1"></i>{{ rt_location() }}</div>
        </div>
        @if ($heroImage)<div class="col-lg-6"><img src="{{ $heroImage }}" class="hero-img" alt=""></div>@endif
    </div>
</div></section>

@if (setting('show_stats') === '1')
<section class="container" style="margin-top:-1.5rem;position:relative;z-index:2"><div class="row g-3 justify-content-center">
    @foreach ([['Kepala Keluarga', $stats['households'], 'bi-house-heart'], ['Jumlah Jiwa', $stats['residents'], 'bi-people'], ['Blok', $stats['blocks'], 'bi-signpost-split']] as [$label, $n, $icon])
        <div class="col-4 col-md-3"><div class="stat text-center"><i class="bi {{ $icon }} fs-4" style="color:var(--brand)"></i><div class="fs-3 fw-bold lh-1 mt-1">{{ number_format($n, 0, ',', '.') }}</div><div class="small text-secondary">{{ $label }}</div></div></div>
    @endforeach
</div></section>
@endif

<section class="container py-5" id="pengurus">
    <div class="text-center mb-4"><h2 class="fw-bold">{{ setting('board_title') ?: 'Pengurus '.rt_name() }}</h2>@if (setting('board_subtitle'))<p class="text-secondary">{{ setting('board_subtitle') }}</p>@endif</div>
    <div class="row g-4 justify-content-center">
        @foreach ([$chair, $vice] as $p)
            <div class="col-md-6 col-lg-4"><div class="profil-card text-center">
                @if ($p['photo'])<img class="avatar mx-auto" src="{{ $p['photo'] }}" alt="{{ $p['name'] }}">@else<div class="avatar mx-auto">{{ initials($p['name']) }}</div>@endif
                <div class="text-uppercase small fw-bold text-secondary mt-3" style="letter-spacing:.08em">{{ $p['title'] }}</div>
                <h3 class="h5 fw-bold mb-1">{{ $p['name'] }}</h3>
                <div class="small text-secondary mb-3">{{ $p['term'] }}</div>
                @if ($p['message'])<p class="mb-0 text-secondary">&ldquo;{{ $p['message'] }}&rdquo;</p>@endif
            </div></div>
        @endforeach
    </div>
</section>

<section class="container pb-5"><div class="row g-4">
    @foreach ($features as $f)
        <div class="col-md-6 col-lg-4 d-flex gap-3"><div class="ficon"><i class="bi {{ in_array($f['icon'] ?? '', config('rt.feature_icons')) ? $f['icon'] : 'bi-star' }}"></i></div><div><h3 class="h6 fw-bold mb-1">{{ $f['title'] ?? '' }}</h3><p class="text-secondary mb-0">{{ $f['text'] ?? '' }}</p></div></div>
    @endforeach
</div></section>

<footer class="border-top bg-white"><div class="container py-3 small text-secondary d-flex justify-content-between flex-wrap gap-2">
    <span>@if (setting('footer_left')){{ setting('footer_left') }}@else{{ rt_name() }} &middot; {{ rt_location() }}@if (setting('motto')) &middot; <em>{{ setting('motto') }}</em>@endif @endif</span>
    <span>{{ setting('footer_right') }}</span>
</div></footer>
@endsection
