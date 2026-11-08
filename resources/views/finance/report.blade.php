@extends('layouts.app', ['title' => 'Laporan Keuangan', 'subtitle' => 'Rincian pemasukan dan pengeluaran kas RT yang sudah disetujui. Bisa dicetak atau disimpan sebagai PDF.'])

@section('content')
<div class="panel mb-3 no-print">
    <form class="row g-2 align-items-end">
        <div class="col-sm-4 col-md-3"><label class="form-label" for="dari">Dari bulan</label><input type="month" id="dari" name="dari" class="form-control" value="{{ $from }}"></div>
        <div class="col-sm-4 col-md-3"><label class="form-label" for="sampai">Sampai bulan</label><input type="month" id="sampai" name="sampai" class="form-control" value="{{ $to }}"></div>
        <div class="col-sm-4 col-md-6 d-flex flex-wrap gap-2">
            <button class="btn btn-primary">Tampilkan</button>
            <a class="btn btn-light" href="{{ route('finance.report', ['dari' => now()->year.'-01', 'sampai' => now()->year.'-12']) }}">Tahun {{ now()->year }}</a>
            <button type="button" class="btn btn-light" onclick="print()"><i class="bi bi-printer me-1"></i>Cetak / PDF</button>
        </div>
    </form>
    @if ($pending)<div class="small text-warning-emphasis mt-2"><i class="bi bi-hourglass-split me-1"></i>{{ $pending }} pengeluaran pada periode ini masih menunggu persetujuan dan belum masuk laporan.</div>@endif
</div>

<div class="lap-print">
    <div class="d-none d-print-block mb-2">@include('letters.partials.letterhead')</div>
    <div class="text-center mb-3"><h2 class="h4 fw-bold mb-0">LAPORAN KEUANGAN KAS {{ mb_strtoupper(rt_name()) }}</h2><div class="text-secondary">Periode {{ $periodLabel }}</div></div>

    <div class="row g-3 mb-3">
        <div class="col-md-6"><div class="panel h-100"><h3 class="h5 mb-3">Ringkasan</h3>
            <table class="table lap-sum mb-0"><tbody>
                <tr><td>Saldo awal (per {{ $start->translatedFormat('j F Y') }})</td><td class="text-end">{{ rupiah($opening) }}</td></tr>
                <tr><td>Total pemasukan</td><td class="text-end text-success">+ {{ rupiah($totalIn) }}</td></tr>
                <tr><td>Total pengeluaran</td><td class="text-end text-danger">- {{ rupiah($totalOut) }}</td></tr>
                <tr class="fw-bold"><td>Saldo akhir (per {{ $end->translatedFormat('j F Y') }})</td><td class="text-end">{{ rupiah($opening + $totalIn - $totalOut) }}</td></tr>
            </tbody></table></div></div>
        <div class="col-md-6"><div class="panel h-100"><h3 class="h5 mb-3">Per kategori</h3>
            <table class="table table-sm mb-0"><thead><tr><th>Kategori</th><th class="text-end">Masuk</th><th class="text-end">Keluar</th></tr></thead><tbody>
            @forelse ($byCategory as $cat => $v)
                <tr><td>{{ $cat }}</td><td class="text-end">{{ $v['masuk'] ? rupiah($v['masuk']) : '-' }}</td><td class="text-end">{{ $v['keluar'] ? rupiah($v['keluar']) : '-' }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-secondary">-</td></tr>
            @endforelse
            </tbody></table></div></div>
    </div>

    @foreach ([['Rincian pemasukan', $in, $totalIn, 'text-success'], ['Rincian pengeluaran', $out, $totalOut, 'text-danger']] as [$judul, $list, $total, $warna])
        <div class="panel mb-3 table-responsive"><h3 class="h5 mb-3">{{ $judul }}</h3>
            <table class="table table-sm align-middle mb-0"><thead><tr><th style="width:44px">No</th><th style="width:110px">Tanggal</th><th>Keterangan</th><th>Kategori</th><th>Alokasi</th><th class="text-end">Nominal</th></tr></thead><tbody>
            @forelse ($list as $i => $r)
                <tr><td>{{ $i + 1 }}</td><td>{{ $r->date->format('d/m/Y') }}</td><td>{{ $r->label() }}</td><td>{{ $r->category }}</td><td>{{ $r->allocation }}</td><td class="text-end">{{ rupiah($r->amount) }}</td></tr>
            @empty
                <tr><td colspan="6" class="text-secondary">Tidak ada transaksi pada periode ini.</td></tr>
            @endforelse
            </tbody><tfoot><tr><th colspan="5" class="text-end">Total</th><th class="text-end {{ $warna }}">{{ rupiah($total) }}</th></tr></tfoot></table>
        </div>
    @endforeach

    <div class="row mt-4 text-center small">
        <div class="col-6"><div>Mengetahui,<br>Ketua {{ rt_name() }}</div><div style="height:64px"></div><div class="fw-semibold"><u>{{ \App\Support\Board::chairName() }}</u></div></div>
        <div class="col-6"><div>{{ setting('city') }}, {{ now()->translatedFormat('j F Y') }}<br>Bendahara</div><div style="height:64px"></div><div class="fw-semibold"><u>{{ $treasurer ?? '................................' }}</u></div></div>
    </div>
    <div class="d-none d-print-block small text-secondary mt-3">Dicetak dari {{ setting('site_name') }} pada {{ now()->format('d/m/Y H:i') }}.</div>
</div>
@endsection
