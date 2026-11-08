@extends('layouts.app', ['title' => 'Pengaturan Situs', 'subtitle' => 'Semua isi halaman depan dan identitas RT. Kolom teks yang dikosongkan kembali ke teks bawaan.'])

@php
    $tab = request('tab', 'identitas');
    $tabs = ['identitas' => 'Identitas & logo', 'wilayah' => 'Wilayah', 'beranda' => 'Bagian utama', 'pengurus' => 'Profil pengurus', 'fitur' => 'Fitur layanan', 'footer' => 'Footer', 'kop' => 'Kop surat', 'bayar' => 'Pembayaran'];
    $text = function (string $key, string $label, string $col = 'col-md-6', bool $area = false) {
        $value = e(old($key, \App\Models\Setting::raw($key)));
        $ph = e(config('rt.defaults.'.$key));
        $field = $area
            ? "<textarea name=\"$key\" id=\"$key\" rows=\"2\" class=\"form-control\" placeholder=\"$ph\">$value</textarea>"
            : "<input name=\"$key\" id=\"$key\" class=\"form-control\" value=\"$value\" placeholder=\"$ph\">";
        return "<div class=\"$col\"><label class=\"form-label\" for=\"$key\">".e($label)."</label>$field</div>";
    };
    $image = function (string $key, string $label, string $hint, string $accept = 'image/*') {
        $url = upload_url(\App\Models\Setting::raw($key));
        $preview = $url ? '<div class="d-flex align-items-center gap-2 mb-2"><img src="'.e($url).'" alt="" style="height:48px;max-width:140px;object-fit:contain;border-radius:8px;background:#f1f5f9"><label class="small"><input type="checkbox" class="form-check-input me-1" name="remove_'.$key.'" value="1">Hapus</label></div>' : '';
        return "<div class=\"col-md-6\"><label class=\"form-label\" for=\"$key\">".e($label)."</label>$preview<input type=\"file\" name=\"$key\" id=\"$key\" accept=\"$accept\" class=\"form-control\"><div class=\"form-text\">".e($hint).'</div></div>';
    };
@endphp

@section('content')
<form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <input type="hidden" name="tab" id="tab" value="{{ $tab }}">
    <div class="d-flex flex-wrap gap-2 mb-3 sticky-top py-2" style="background:var(--bg);z-index:10">
        <ul class="nav nav-pills" role="tablist">
            @foreach ($tabs as $id => $label)
                <li class="nav-item"><button type="button" class="nav-link @if ($tab === $id) active @endif" data-bs-toggle="pill" data-bs-target="#t-{{ $id }}" data-tab="{{ $id }}">{{ $label }}</button></li>
            @endforeach
        </ul>
        <div class="ms-auto d-flex gap-2"><a href="{{ route('home') }}" target="_blank" class="btn btn-light"><i class="bi bi-eye me-1"></i>Lihat beranda</a><button class="btn btn-primary">Simpan semua</button></div>
    </div>

    <div class="tab-content">
        <div class="tab-pane fade @if ($tab === 'identitas') show active @endif" id="t-identitas"><div class="panel"><div class="row g-3">
            {!! $text('site_name', 'Nama website *') !!}
            {!! $text('brand_sub', 'Teks di bawah nama (kosongkan untuk otomatis)') !!}
            {!! $image('logo', 'Logo RT', 'JPG/PNG/WEBP, maks. 2 MB. Juga dipakai sebagai logo kiri kop surat.') !!}
            {!! $image('favicon', 'Ikon tab browser (favicon)', '.ico atau .png persegi, maks. 512 KB. Jika kosong, memakai logo RT.', '.ico,image/x-icon,image/png') !!}
            {!! $image('logo_right', 'Logo kanan kop surat (opsional)', 'Dipakai pada modul surat di tahap berikutnya.') !!}
        </div></div></div>

        <div class="tab-pane fade @if ($tab === 'wilayah') show active @endif" id="t-wilayah"><div class="panel"><div class="row g-3">
            {!! $text('rt_number', 'Nomor RT *', 'col-md-3') !!}
            {!! $text('rw_number', 'Nomor RW *', 'col-md-3') !!}
            {!! $text('village', 'Kelurahan / Desa') !!}
            {!! $text('district', 'Kecamatan') !!}
            {!! $text('city', 'Kota / Kabupaten') !!}
            {!! $text('secretariat_address', 'Alamat sekretariat') !!}
            {!! $text('motto', 'Moto RT') !!}
        </div></div></div>

        <div class="tab-pane fade @if ($tab === 'beranda') show active @endif" id="t-beranda"><div class="panel"><div class="row g-3">
            {!! $text('hero_badge', 'Label kecil di atas judul') !!}
            {!! $text('hero_button_primary', 'Teks tombol utama', 'col-md-3') !!}
            {!! $text('hero_button_secondary', 'Teks tombol kedua', 'col-md-3') !!}
            {!! $text('hero_title', 'Judul besar', 'col-12', true) !!}
            {!! $text('hero_lead', 'Kalimat penjelas', 'col-12', true) !!}
            {!! $image('hero_image', 'Gambar di samping judul (opsional)', 'Foto lingkungan, gapura, atau kegiatan warga. Maks. 2 MB.') !!}
            <div class="col-md-6 d-flex align-items-end"><label class="form-check"><input type="checkbox" class="form-check-input" name="show_stats" value="1" @checked(setting('show_stats') === '1')> Tampilkan statistik (jumlah KK, jiwa, blok)</label></div>
        </div></div></div>

        <div class="tab-pane fade @if ($tab === 'pengurus') show active @endif" id="t-pengurus"><div class="panel"><div class="row g-3">
            {!! $text('board_title', 'Judul bagian') !!}
            {!! $text('board_subtitle', 'Subjudul') !!}
            <div class="col-12"><label class="form-label">Sumber nama dan foto</label>
                <div class="d-flex flex-wrap gap-3">
                    <label class="form-check"><input type="radio" class="form-check-input" name="profile_source" value="account" @checked(setting('profile_source') !== 'manual')> Otomatis dari akun berjabatan Ketua/Wakil (ikut berganti saat pergantian pengurus)</label>
                    <label class="form-check"><input type="radio" class="form-check-input" name="profile_source" value="manual" @checked(setting('profile_source') === 'manual')> Isian manual di bawah</label>
                </div></div>
            <div class="col-12"><h3 class="h6 fw-bold mt-2 mb-0">Ketua RT</h3></div>
            {!! $text('chair_name', 'Nama (mode manual)') !!}
            {!! $text('chair_term', 'Masa jabatan') !!}
            {!! $text('chair_message', 'Sambutan', 'col-12', true) !!}
            {!! $image('chair_photo', 'Foto Ketua (mode manual)', 'Foto persegi, maks. 2 MB.') !!}
            <div class="col-12"><h3 class="h6 fw-bold mt-2 mb-0">Wakil Ketua RT</h3></div>
            {!! $text('vice_name', 'Nama (mode manual)') !!}
            {!! $text('vice_term', 'Masa jabatan') !!}
            {!! $text('vice_message', 'Pesan singkat', 'col-12', true) !!}
            {!! $image('vice_photo', 'Foto Wakil (mode manual)', 'Foto persegi, maks. 2 MB.') !!}
        </div></div></div>

        <div class="tab-pane fade @if ($tab === 'fitur') show active @endif" id="t-fitur"><div class="panel">
            <div class="small text-secondary mb-3">Daftar layanan di halaman depan. Maksimal 9 item; baris dengan judul kosong diabaikan.</div>
            <div id="fiturBox">
                @foreach ($features as $i => $f)
                    @include('admin.partials.feature-row', ['i' => $i, 'f' => $f])
                @endforeach
            </div>
            <template id="fiturTpl">@include('admin.partials.feature-row', ['i' => '__I__', 'f' => ['icon' => 'bi-star', 'title' => '', 'text' => '']])</template>
            <button type="button" class="btn btn-light" id="addFitur"><i class="bi bi-plus-lg me-1"></i>Tambah fitur</button>
        </div></div>

        <div class="tab-pane fade @if ($tab === 'kop') show active @endif" id="t-kop"><div class="row g-3">
            <div class="col-xl-7"><div class="panel"><div class="row g-3">
                <div class="col-12 small text-secondary">Kosongkan baris untuk memakai teks otomatis dari tab Wilayah. Logo kiri = logo RT, logo kanan diatur di tab Identitas &amp; logo. Moto RT tampil di kaki surat.</div>
                {!! $text('letterhead_line1', 'Baris 1 (instansi)', 'col-12') !!}
                {!! $text('letterhead_line2', 'Baris 2 (kecamatan)', 'col-12') !!}
                {!! $text('letterhead_line3', 'Baris 3 (kelurahan/desa)', 'col-12') !!}
                {!! $text('letterhead_line4', 'Baris 4 (RT/RW)', 'col-12') !!}
                {!! $text('public_url', 'Alamat publik website untuk QR (opsional), mis. https://rt05.contoh.id', 'col-12') !!}
                <div class="col-12 form-text mt-0">QR tanda tangan mengarah ke alamat ini. Jika kosong, memakai APP_URL. Jangan biarkan "localhost" jika surat akan dipindai warga.</div>
            </div></div></div>
            <div class="col-xl-5"><div class="panel"><h2 class="h6 fw-bold">Pratinjau kop (tersimpan)</h2>
                <div class="a4-stage rounded-3"><div class="a4" style="zoom:.45;min-height:0;padding:12mm">@include('letters.partials.letterhead')</div></div></div></div>
        </div></div>

        <div class="tab-pane fade @if ($tab === 'bayar') show active @endif" id="t-bayar"><div class="panel"><div class="row g-3">
            <div class="col-12">
                <label class="form-label" for="payment_info">Rekening / e-wallet tujuan transfer</label>
                <textarea name="payment_info" id="payment_info" rows="4" class="form-control" placeholder="{{ config('rt.defaults.payment_info') }}">{{ old('payment_info', \App\Models\Setting::raw('payment_info')) }}</textarea>
                <div class="form-text">Tampil di dasbor warga sebagai petunjuk membayar tagihan. Pembayaran masih manual: warga mengunggah bukti, Bendahara memverifikasi.</div>
            </div>
        </div></div></div>

        <div class="tab-pane fade @if ($tab === 'footer') show active @endif" id="t-footer"><div class="panel"><div class="row g-3">
            {!! $text('footer_left', 'Teks kiri (kosongkan untuk otomatis)') !!}
            {!! $text('footer_right', 'Teks kanan') !!}
        </div></div></div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const box = document.getElementById('fiturBox'), tpl = document.getElementById('fiturTpl').innerHTML;
    let n = box.children.length + 100;
    const ikon = el => { const s = el.querySelector('select'), i = el.querySelector('.ficon i'); if (s && i) s.onchange = () => i.className = 'bi ' + s.value; };
    box.querySelectorAll('.fitur-row').forEach(ikon);
    box.addEventListener('click', e => { if (e.target.closest('[data-hapus]')) e.target.closest('.fitur-row').remove(); });
    document.getElementById('addFitur').onclick = () => {
        if (box.children.length >= 9) return alert('Maksimal 9 fitur.');
        box.insertAdjacentHTML('beforeend', tpl.replaceAll('__I__', n++)); ikon(box.lastElementChild);
    };
    document.querySelectorAll('[data-tab]').forEach(b => b.addEventListener('shown.bs.tab', () => document.getElementById('tab').value = b.dataset.tab));
})();
</script>
@endpush
