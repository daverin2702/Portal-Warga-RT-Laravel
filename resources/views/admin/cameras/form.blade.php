@use('App\Models\Camera')
@extends('layouts.app', ['title' => $camera->exists ? 'Ubah Kamera' : 'Kamera Baru', 'subtitle' => 'Nama dan titik pemasangan tampil di layar pantau warga.'])

@section('content')
<form method="post" action="{{ $camera->exists ? route('admin.cameras.update', $camera) : route('admin.cameras.store') }}" class="row g-3">
    @csrf @if ($camera->exists) @method('PUT') @endif
    <div class="col-lg-7"><div class="panel">
        <h2 class="h5 mb-3">Penempatan & penamaan</h2>
        <div class="row g-2">
            <div class="col-md-7"><label class="form-label" for="name">Nama kamera *</label><input id="name" name="name" class="form-control" value="{{ old('name', $camera->name) }}" placeholder="mis. Gerbang Utama" required maxlength="80"></div>
            <div class="col-md-5"><label class="form-label" for="brand">Merek</label><input id="brand" name="brand" list="merek" class="form-control" value="{{ old('brand', $camera->brand) }}" maxlength="40">
                <datalist id="merek">@foreach (Camera::BRANDS as $b)<option value="{{ $b }}">@endforeach</datalist></div>
            <div class="col-md-5"><label class="form-label" for="area">Area / kelompok</label><input id="area" name="area" class="form-control" value="{{ old('area', $camera->area) }}" placeholder="mis. Gerbang, Blok A, Taman" maxlength="60"></div>
            <div class="col-md-7"><label class="form-label" for="location">Titik pemasangan</label><input id="location" name="location" class="form-control" value="{{ old('location', $camera->location) }}" placeholder="mis. Tiang pos ronda menghadap jalan masuk" maxlength="150"></div>
            <div class="col-12"><label class="form-label" for="description">Keterangan</label><input id="description" name="description" class="form-control" value="{{ old('description', $camera->description) }}" maxlength="250"></div>
            <div class="col-md-4"><label class="form-label" for="sort_order">Urutan tampil</label><input id="sort_order" type="number" min="0" max="999" name="sort_order" class="form-control" value="{{ old('sort_order', $camera->sort_order ?? 0) }}"></div>
            <div class="col-md-8"><label class="form-label" for="visibility">Bisa dilihat oleh</label><select id="visibility" name="visibility" class="form-select">
                @foreach (Camera::VISIBILITY as $k => $v)<option value="{{ $k }}" @selected(old('visibility', $camera->visibility) === $k)>{{ $v }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-check"><input type="checkbox" class="form-check-input" name="active" value="1" @checked(old('active', $camera->active))> Aktif (tampil di layar pantau)</label></div>
        </div>
    </div></div>

    <div class="col-lg-5"><div class="panel">
        <h2 class="h5 mb-3">Konektivitas</h2>
        <label class="form-label" for="type">Jenis koneksi *</label>
        <select id="type" name="type" class="form-select mb-2">@foreach (Camera::TYPES as $k => $v)<option value="{{ $k }}" @selected(old('type', $camera->type) === $k)>{{ $v }}</option>@endforeach</select>

        <label class="form-label" for="source">Alamat sumber {{ $camera->exists ? '' : '*' }}</label>
        <textarea id="source" name="source" rows="3" class="form-control font-monospace small" maxlength="2000" placeholder="{{ $camera->exists ? 'Kosongkan bila tidak diubah' : '' }}">{{ old('source') }}</textarea>
        @if ($camera->exists)<div class="small text-secondary mt-1">Tersimpan: <code class="text-break">{{ $camera->maskedSource() }}</code></div>@endif

        <div class="hint mt-2" data-for="rtsp">
            <div class="row g-2 mb-2"><div class="col-12"><label class="form-label" for="stream_key">Nama aliran di gateway</label><input id="stream_key" name="stream_key" class="form-control" value="{{ old('stream_key', $camera->stream_key) }}" placeholder="otomatis dari nama, mis. gerbang_utama" maxlength="60"></div></div>
            <div class="small text-secondary">Contoh alamat per merek:
                <ul class="mb-0 ps-3">
                    <li>Hikvision: <code>rtsp://admin:SANDI@192.168.1.64:554/Streaming/Channels/101</code></li>
                    <li>Dahua / Imou: <code>rtsp://admin:SANDI@IP:554/cam/realmonitor?channel=1&amp;subtype=0</code></li>
                    <li>EZVIZ: <code>rtsp://admin:KODE-VERIFIKASI@IP:554/h264/ch1/main/av_stream</code></li>
                    <li>TP-Link Tapo: <code>rtsp://AKUN-KAMERA:SANDI@IP:554/stream1</code></li>
                    <li>Reolink: <code>rtsp://admin:SANDI@IP:554/h264Preview_01_main</code></li>
                    <li>NVR/DVR lain: lihat menu "Network &rarr; RTSP" di perangkat atau manual merek.</li>
                </ul>Sandi disimpan terenkripsi dan tidak pernah dikirim ke browser.</div>
        </div>
        <div class="hint mt-2 small text-secondary" data-for="hls">Alamat <code>.m3u8</code> dari NVR, layanan cloud, atau gateway, mis. <code>http://192.168.1.10:8888/gerbang/index.m3u8</code>. Alamat ini terlihat oleh browser penonton.</div>
        <div class="hint mt-2 small text-secondary" data-for="mjpeg">Alamat aliran MJPEG, mis. Axis <code>http://IP/axis-cgi/mjpg/video.cgi</code>. Alamat ini terlihat oleh browser penonton, jadi jangan sertakan sandi; untuk kamera bersandi gunakan RTSP + gateway atau Snapshot lewat server.</div>
        <div class="hint mt-2" data-for="snapshot">
            <div class="small text-secondary mb-2">Alamat gambar JPEG, mis. Hikvision <code>http://admin:SANDI@IP/ISAPI/Streaming/channels/101/picture</code>, Dahua <code>http://admin:SANDI@IP/cgi-bin/snapshot.cgi</code>. Basic dan Digest auth didukung.</div>
            <div class="row g-2 align-items-end"><div class="col-5"><label class="form-label" for="refresh_seconds">Perbarui tiap (detik)</label><input id="refresh_seconds" type="number" min="1" max="300" name="refresh_seconds" class="form-control" value="{{ old('refresh_seconds', $camera->refresh_seconds ?? 5) }}"></div>
                <div class="col-7"><label class="form-check"><input type="checkbox" class="form-check-input" name="via_server" value="1" @checked(old('via_server', $camera->via_server ?? true))> Ambil lewat server portal (sandi tersembunyi)</label></div></div>
        </div>
        <div class="hint mt-2 small text-secondary" data-for="embed">Tempel <strong>tautan berbagi</strong> atau <strong>kode &lt;iframe&gt;</strong> dari layanan cloud CCTV (mis. Ivideon, Angelcam, RTSP.me, portal web merek kamera), dari halaman go2rtc/MediaMTX, atau dari situs mana pun yang menyediakan embed. Sebagian aplikasi merek (Hik-Connect, EZVIZ, Imou, Tapo) tidak menyediakan embed; gunakan RTSP + gateway untuk merek tersebut.</div>
        <div class="hint mt-2 small text-secondary" data-for="youtube">Tempel tautan siaran/video YouTube (<code>youtube.com/live/...</code>, <code>youtu.be/...</code>), ID video, atau ID kanal (<code>UC...</code>) untuk selalu memutar siaran langsung kanal itu.</div>
    </div></div>

    <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Simpan kamera</button><a href="{{ route('admin.cameras.index') }}" class="btn btn-link">Batal</a></div>
</form>
@endsection

@push('scripts')
<script>
(function () {
  const sel = document.getElementById('type');
  const tampil = () => document.querySelectorAll('.hint').forEach(h => { h.hidden = h.dataset.for !== sel.value; });
  sel.addEventListener('change', tampil); tampil();
})();
</script>
@endpush
