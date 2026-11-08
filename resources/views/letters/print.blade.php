@php
    $terbit = $letter->isIssued();
    if (! $terbit) {
        $ttd = '<span style="display:inline-block;border:1px dashed #999;padding:6px 10px;font-size:8pt;color:#777">Tanda tangan muncul<br>setelah disetujui Ketua</span>';
    } elseif ($letter->qr_signature) {
        $ttd = '<span class="qr-ttd" data-url="'.e($letter->verifyUrl()).'"></span>';
    } else {
        $ttd = '<span style="display:inline-block;height:24mm"></span>';
    }
    $isi = preg_replace('/\{\{\s*qr_ttd\s*\}\}/i', $ttd, $letter->body);
@endphp
<!doctype html>
<html lang="id" data-theme="{{ active_theme() }}">
<head>@include('partials.head', ['title' => $letter->title.' '.$letter->number])</head>
<body>
<div class="container-fluid py-3 d-flex gap-2 flex-wrap align-items-center no-print">
    <a class="btn btn-light" href="{{ auth()->user()->isBoard() ? route('letters.archive') : route('letters.requests.index') }}">&larr; Kembali</a>
    <button class="btn btn-primary" onclick="print()"><i class="bi bi-printer me-1"></i>Cetak / Simpan PDF</button>
    @unless ($terbit)<span class="badge text-bg-warning">Draf: {{ $letter->status }}</span>@endunless
    <span class="text-secondary small ms-2">Di jendela cetak, pilih ukuran A4 dan matikan &ldquo;Header dan footer&rdquo;.</span>
</div>
@include('partials.flash')
<div class="a4-stage"><div class="a4">
    @unless ($terbit)<div class="wm">DRAF</div>@endunless
    @if ($letter->with_letterhead)@include('letters.partials.letterhead')@endif
    <div class="a4-body">{!! $isi !!}</div>
    @if ($terbit && ! $letter->qr_signature)
        <div style="font-size:8pt;color:#555;margin-top:6px">Keaslian surat dapat dicek di {{ url('/') }} (tombol Cek Surat) dengan nomor surat dan tanggal {{ ($letter->issued_at ?? $letter->created_at)->format('d/m/Y') }}.</div>
    @endif
    @if ($letter->with_letterhead)@include('letters.partials.footer')@endif
</div></div>
<script src="{{ rt_asset('bootstrap.js') }}"></script>
<script src="{{ rt_asset('qrcode.js') }}"></script>
<script>
document.querySelectorAll('.qr-ttd').forEach(el => {
    if (!window.QRCode) { el.textContent = '[QR]'; return; }
    new QRCode(el, { text: el.dataset.url, width: 88, height: 88, correctLevel: QRCode.CorrectLevel.M });
    el.insertAdjacentHTML('beforeend', '<div style="font-size:7pt;margin-top:2px">Pindai untuk verifikasi</div>');
});
</script>
</body>
</html>
