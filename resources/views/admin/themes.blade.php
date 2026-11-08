@extends('layouts.app', ['title' => 'Tema Website', 'subtitle' => 'Pilih tampilan untuk seluruh halaman.'])

@section('content')
<div class="row g-3">
    @foreach ($themes as $key => $t)
        @php($on = $active === $key)
        @php($w = $t['swatch'])
        <div class="col-md-6 col-xl-4"><div class="tema-card {{ $on ? 'aktif' : '' }}">
            <div class="d-flex justify-content-between align-items-start"><h2 class="h5 fw-bold mb-1">{{ $t['name'] }}</h2>@if ($on)<span class="badge text-bg-success">Aktif</span>@endif</div>
            <p class="small text-secondary mb-2">{{ $t['description'] }}</p>
            <div class="swatch">@foreach ($w as $c)<span style="background:{{ $c }}" title="{{ $c }}"></span>@endforeach</div>
            <div class="mini" style="background:{{ $w[3] }}">
                <div class="sb" style="background:{{ $key === 'biru' ? '#132F52' : '#fff' }};border-right:1px solid #e5e9f0">
                    <div class="ln" style="width:60%;background:{{ $w[2] }}"></div>
                    <div class="ln" style="background:{{ $key === 'biru' ? 'rgba(255,255,255,.18)' : $w[1] }}"></div>
                    <div class="ln" style="width:80%;background:{{ $key === 'biru' ? 'rgba(255,255,255,.1)' : '#eef1f5' }}"></div>
                </div>
                <div class="ct">
                    <div class="ln" style="width:45%;height:9px;background:#334155"></div>
                    <div class="d-flex gap-1 mb-2">@for ($i = 0; $i < 3; $i++)<div style="flex:1;height:30px;background:#fff;border-radius:{{ $key === 'terakota' ? 10 : 6 }}px"></div>@endfor</div>
                    <div style="width:38%;height:14px;border-radius:{{ $key === 'terakota' ? 99 : 5 }}px;background:{{ $w[0] }}"></div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <form method="post" action="{{ route('admin.themes.update') }}">@csrf @method('PUT')<button name="theme" value="{{ $key }}" class="btn btn-primary btn-sm" @disabled($on)>{{ $on ? 'Sedang dipakai' : 'Terapkan' }}</button></form>
                <a class="btn btn-light btn-sm" target="_blank" href="{{ route('dashboard', ['pratinjau_tema' => $key]) }}">Pratinjau dasbor</a>
            </div>
        </div></div>
    @endforeach
</div>
<div class="small text-secondary mt-3">Pratinjau hanya terlihat oleh Anda dan tidak mengubah tampilan warga sampai tombol Terapkan ditekan.</div>
@endsection
