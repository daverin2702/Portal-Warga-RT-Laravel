@use('App\Models\Camera')
@extends('layouts.app', ['title' => 'Kelola CCTV', 'subtitle' => 'Atur penempatan, nama, dan koneksi kamera. Semua akun bisa menonton dari menu CCTV Lingkungan.'])

@section('content')
<div class="row g-3 mb-4">
    <div class="col-lg-7"><div class="panel h-100">
        <h2 class="h5 mb-1">Gateway untuk kamera RTSP</h2>
        <p class="small text-secondary">Browser tidak bisa memutar RTSP secara langsung. Pasang <strong>go2rtc</strong> atau <strong>MediaMTX</strong> di komputer/mini PC yang satu jaringan dengan kamera, lalu isi alamatnya di sini. Portal hanya mengirim <em>nama aliran</em> ke browser, bukan sandi kamera.</p>
        <form method="post" action="{{ route('admin.cameras.gateway') }}" class="row g-2 align-items-end">
            @csrf @method('PUT')
            <div class="col-md-4"><label class="form-label" for="cctv_gateway">Jenis gateway</label><select id="cctv_gateway" name="cctv_gateway" class="form-select">
                <option value="go2rtc" @selected(setting('cctv_gateway') !== 'mediamtx')>go2rtc</option>
                <option value="mediamtx" @selected(setting('cctv_gateway') === 'mediamtx')>MediaMTX</option></select></div>
            <div class="col-md-5"><label class="form-label" for="cctv_gateway_url">Alamat gateway</label><input id="cctv_gateway_url" name="cctv_gateway_url" class="form-control" value="{{ setting('cctv_gateway_url') }}" placeholder="http://192.168.1.10:1984"></div>
            <div class="col-md-3"><button class="btn btn-primary w-100">Simpan</button></div>
        </form>
        <div class="small text-secondary mt-2">go2rtc: port <code>1984</code>. MediaMTX: alamat WebRTC, port <code>8889</code>. Jika portal memakai <strong>https</strong>, gateway juga harus https agar tidak diblokir browser.</div>
    </div></div>
    <div class="col-lg-5"><div class="panel h-100">
        <h2 class="h5 mb-1">Berkas konfigurasi</h2>
        <p class="small text-secondary mb-2">Dibuat otomatis dari {{ $rtspCount }} kamera RTSP. Salin ke folder gateway lalu jalankan ulang gateway-nya.</p>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-light btn-sm" href="{{ route('admin.cameras.config', 'go2rtc') }}"><i class="bi bi-download me-1"></i>go2rtc.yaml</a>
            <a class="btn btn-light btn-sm" href="{{ route('admin.cameras.config', 'mediamtx') }}"><i class="bi bi-download me-1"></i>mediamtx (paths)</a>
        </div>
        <div class="alert alert-warning small mt-3 mb-0"><i class="bi bi-shield-lock me-1"></i>Berkas ini berisi sandi kamera. Jangan dibagikan.</div>
    </div></div>
</div>

<div class="panel table-responsive">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.cameras.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Kamera baru</a>
        @if ($cameras->isNotEmpty())<form method="post" action="{{ route('admin.cameras.test-all') }}">@csrf<button class="btn btn-light"><i class="bi bi-activity me-1"></i>Tes semua koneksi</button></form>@endif
        <a href="{{ route('cctv.index') }}" class="btn btn-light ms-auto"><i class="bi bi-display me-1"></i>Lihat tampilan warga</a>
    </div>
    <table class="table align-middle mb-0">
        <thead><tr><th>Kamera</th><th>Area · titik</th><th>Koneksi</th><th>Status</th><th>Dilihat</th><th></th></tr></thead>
        <tbody>
        @forelse ($cameras as $c)
            <tr class="{{ $c->active ? '' : 'text-secondary' }}">
                <td><strong>{{ $c->name }}</strong>@if ($c->brand)<div class="small text-secondary">{{ $c->brand }}</div>@endif @unless ($c->active)<span class="badge text-bg-secondary">Nonaktif</span>@endunless</td>
                <td>{{ $c->area ?: '-' }}<div class="small text-secondary">{{ $c->location }}</div></td>
                <td><span class="badge text-bg-light border text-uppercase">{{ $c->type }}</span><div class="small text-secondary text-break" style="max-width:280px">{{ Str::limit($c->maskedSource(), 70) }}</div>
                    @if ($c->stream_key)<div class="small">aliran: <code>{{ $c->stream_key }}</code></div>@endif</td>
                <td>
                    @if ($c->last_status === 'online')<span class="badge text-bg-success">Terhubung</span>
                    @elseif ($c->last_status === 'offline')<span class="badge text-bg-danger">Gagal</span>
                    @else<span class="badge text-bg-light border">Belum dites</span>@endif
                    @if ($c->last_checked_at)<div class="small text-secondary" title="{{ $c->last_message }}">{{ $c->last_checked_at->diffForHumans() }}</div>@endif
                </td>
                <td class="small">{{ $c->visibility === 'pengurus' ? 'Pengurus' : 'Semua' }}</td>
                <td class="text-nowrap text-end">
                    <form method="post" action="{{ route('admin.cameras.test', $c) }}" class="d-inline">@csrf<button class="btn btn-sm btn-light">Tes</button></form>
                    <a class="btn btn-sm btn-light" href="{{ route('admin.cameras.edit', $c) }}">Ubah</a>
                    <form method="post" action="{{ route('admin.cameras.destroy', $c) }}" class="d-inline" onsubmit="return confirm(@js('Hapus kamera '.$c->name.'?'))">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger">Hapus</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-secondary">Belum ada kamera. Tambahkan kamera pertama dengan tombol "Kamera baru".</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
