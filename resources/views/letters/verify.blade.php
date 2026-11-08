@extends('layouts.guest', ['title' => 'Verifikasi Surat'])

@section('content')
<div class="login-wrap"><div class="panel p-4 text-center" style="max-width:460px;width:100%;border-radius:20px">
    @if ($data)
        <div class="ficon mx-auto mb-3" style="background:#e3f6ec;color:#1a8f5a"><i class="bi bi-patch-check-fill"></i></div>
        <h1 class="h4 fw-bold">Surat asli dan berlaku</h1>
        <p class="text-secondary">Tercatat resmi di arsip {{ rt_name() }}. Sebagian data disamarkan untuk melindungi privasi warga.</p>
        <table class="table table-sm text-start mb-0"><tbody>
            @foreach (['nomor' => 'Nomor', 'jenis' => 'Jenis', 'tanggal' => 'Tanggal', 'pemohon' => 'Pemohon', 'tujuan' => 'Tujuan', 'penandatangan' => 'Ditandatangani', 'ttd' => 'Tanda tangan'] as $k => $label)
                <tr><th>{{ $label }}</th><td>{{ $data[$k] }}</td></tr>
            @endforeach
        </tbody></table>
    @elseif ($letter)
        <div class="ficon mx-auto mb-3" style="background:#fff4d6;color:#b07d00"><i class="bi bi-hourglass-split"></i></div>
        <h1 class="h4 fw-bold">Surat belum berlaku</h1>
        <p class="text-secondary mb-0">Surat ini tercatat tetapi belum disetujui Ketua RT.</p>
    @else
        <div class="ficon mx-auto mb-3" style="background:#fde8e8;color:#c0392b"><i class="bi bi-x-octagon-fill"></i></div>
        <h1 class="h4 fw-bold">Surat tidak ditemukan</h1>
        <p class="text-secondary mb-0">Kode tidak terdaftar atau surat sudah dicabut. Hubungi pengurus RT untuk memastikan.</p>
    @endif
    <a href="{{ route('home') }}" class="d-inline-block mt-3 small">Ke halaman depan {{ setting('site_name') }}</a>
</div></div>
@endsection
