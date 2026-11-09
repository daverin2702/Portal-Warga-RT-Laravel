@extends('layouts.app', ['title' => 'CCTV Lingkungan', 'subtitle' => 'Pantau kamera lingkungan secara langsung. Laporkan kejadian lewat tombol Darurat atau menu Aduan.'])

@section('content')
@if ($total === 0)
    <div class="panel text-secondary">
        Belum ada kamera yang ditampilkan.
        @if (auth()->user()->isAdmin())<a href="{{ route('admin.cameras.create') }}">Tambahkan kamera</a>.@endif
    </div>
@else
    <div class="panel mb-3 d-flex flex-wrap align-items-center gap-2 py-2">
        <div class="d-flex flex-wrap gap-1" id="filterArea">
            <button type="button" class="btn btn-sm btn-primary" data-area="">Semua ({{ $total }})</button>
            @foreach ($groups as $area => $list)<button type="button" class="btn btn-sm btn-light" data-area="{{ $area }}">{{ $area }} ({{ $list->count() }})</button>@endforeach
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
            <span class="small text-secondary d-none d-sm-inline">Tata letak</span>
            <div class="btn-group btn-group-sm" role="group" aria-label="Jumlah kolom" id="kolom">
                @foreach ([1, 2, 3, 4] as $n)<button type="button" class="btn btn-light" data-col="{{ $n }}" title="{{ $n }} kolom"><i class="bi bi-{{ [1 => 'square', 2 => 'grid', 3 => 'grid-3x2', 4 => 'grid-3x3'][$n] }}"></i></button>@endforeach
            </div>
            <span class="small text-secondary font-monospace" id="jamCctv"></span>
        </div>
    </div>

    <div class="cctv-grid" id="cctvGrid" data-col="2">
        @foreach ($groups as $area => $list)
            @foreach ($list as $c)
                @php($p = $c->player())
                <div class="cctv-tile" data-area="{{ $area }}">
                    <div class="cctv-view" data-kind="{{ $p['kind'] }}" data-src="{{ $p['src'] ?? '' }}" data-refresh="{{ $p['refresh'] ?? 0 }}" data-error="{{ $p['error'] ?? '' }}">
                        <div class="cctv-msg"><div class="spinner-border spinner-border-sm"></div><span>Menghubungkan...</span></div>
                        <span class="cctv-live"><i class="bi bi-record-fill"></i> LIVE</span>
                        <button type="button" class="cctv-fs" title="Layar penuh" aria-label="Layar penuh {{ $c->name }}"><i class="bi bi-arrows-fullscreen"></i></button>
                    </div>
                    <div class="cctv-cap">
                        <div class="text-truncate"><strong>{{ $c->name }}</strong><div class="small text-secondary text-truncate">{{ collect([$area, $c->location])->filter()->join(' · ') }}</div></div>
                        @if ($c->visibility === 'pengurus')<span class="badge text-bg-light border" title="Hanya terlihat oleh pengurus"><i class="bi bi-lock"></i></span>@endif
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>
    <div class="small text-secondary mt-3"><i class="bi bi-info-circle me-1"></i>Kamera diputar tanpa suara dan hanya saat terlihat di layar untuk menghemat kuota. Jika tidak tampil, coba lagi beberapa saat atau beri tahu pengurus.</div>
@endif
@endsection

@push('scripts')
@if ($groups->flatten()->contains(fn ($c) => $c->type === 'hls'))<script src="{{ rt_asset('hls.js') }}"></script>@endif
<script src="{{ asset('js/cctv.js') }}?v={{ filemtime(public_path('js/cctv.js')) }}"></script>
@endpush
