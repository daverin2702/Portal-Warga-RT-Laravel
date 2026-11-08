@extends('layouts.app', ['title' => 'Kas RT '.$year, 'subtitle' => 'Hanya transaksi yang sudah disetujui yang dihitung dalam saldo dan laporan.'])

@php($bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'])

@section('content')
<div class="panel mb-3"><form class="row g-2 align-items-end">
    <div class="col-sm-4 col-md-3"><label class="form-label" for="tahun">Tahun</label><select id="tahun" name="tahun" class="form-select" onchange="this.form.submit()">@foreach ($years as $y)<option @selected($y == $year)>{{ $y }}</option>@endforeach</select></div>
    <div class="col-sm-4 col-md-3"><label class="form-label" for="bulan">Bulan (mutasi)</label><select id="bulan" name="bulan" class="form-select" onchange="this.form.submit()"><option value="0">Semua bulan</option>@for ($i = 1; $i <= 12; $i++)<option value="{{ $i }}" @selected($month === $i)>{{ $bulan[$i] }}</option>@endfor</select></div>
    <div class="col-sm-4 col-md-6"><a class="btn btn-light" href="{{ route('finance.report', ['dari' => $year.'-01', 'sampai' => $year.'-12']) }}"><i class="bi bi-printer me-1"></i>Laporan tahunan</a></div>
</form></div>

<div class="row g-3 mb-4">
    @foreach ([['Pemasukan '.$year, $totals['masuk']], ['Pengeluaran '.$year, $totals['keluar']], ['Selisih '.$year, $totals['masuk'] - $totals['keluar']], ['Saldo kas saat ini', $balance]] as [$label, $v])
        <div class="col-6 col-lg-3"><div class="stat"><div class="small text-secondary">{{ $label }}</div><div class="fs-4 fw-bold">{{ rupiah($v) }}</div></div></div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7"><div class="panel h-100 table-responsive"><h2 class="h5 mb-3">Laporan bulanan {{ $year }}</h2>
        <table class="table mb-0"><thead><tr><th>Bulan</th><th class="text-end">Pemasukan</th><th class="text-end">Pengeluaran</th><th class="text-end">Selisih</th></tr></thead><tbody>
        @for ($i = 1; $i <= 12; $i++)
            @php($r = $monthly[$i] ?? null)
            @continue(! $r && $year == now()->year && $i > now()->month)
            <tr><td><a href="{{ route('finance.cash.index', ['tahun' => $year, 'bulan' => $i]) }}#mutasi">{{ $bulan[$i] }}</a></td>
                <td class="text-end text-success">{{ rupiah($r->masuk ?? 0) }}</td><td class="text-end text-danger">{{ rupiah($r->keluar ?? 0) }}</td>
                <td class="text-end fw-semibold">{{ rupiah(($r->masuk ?? 0) - ($r->keluar ?? 0)) }}</td></tr>
        @endfor
        </tbody></table></div></div>
    <div class="col-lg-5"><div class="panel h-100 table-responsive"><h2 class="h5 mb-3">Dana per alokasi</h2>
        <table class="table mb-0"><thead><tr><th>Alokasi</th><th class="text-end">Sisa</th></tr></thead><tbody>
        @forelse ($allocations as $a)
            <tr><td>{{ $a->allocation }}<div class="small text-secondary">Masuk {{ rupiah($a->masuk) }} · Keluar {{ rupiah($a->keluar) }}</div></td><td class="text-end fw-semibold">{{ rupiah($a->masuk - $a->keluar) }}</td></tr>
        @empty
            <tr><td colspan="2" class="text-secondary">Belum ada transaksi.</td></tr>
        @endforelse
        </tbody></table></div></div>
</div>

@can('catat-kas')
<div class="panel mb-4"><h2 class="h5 mb-3">Posting pengeluaran / catat pemasukan</h2>
    <form method="post" action="{{ route('finance.cash.store') }}" class="row g-2">@csrf
        <div class="col-md-2"><label class="form-label" for="c-date">Tanggal</label><input id="c-date" type="date" name="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required></div>
        <div class="col-md-2"><label class="form-label" for="c-type">Jenis</label><select id="c-type" name="type" class="form-select"><option value="keluar" @selected(old('type') !== 'masuk')>Pengeluaran</option><option value="masuk" @selected(old('type') === 'masuk')>Pemasukan</option></select></div>
        <div class="col-md-2"><label class="form-label" for="c-cat">Kategori</label><input id="c-cat" name="category" class="form-control" value="{{ old('category') }}" placeholder="mis. Listrik pos" required maxlength="60"></div>
        <div class="col-md-2"><label class="form-label" for="c-alloc">Alokasi</label><input id="c-alloc" name="allocation" list="alokasi2" class="form-control" value="{{ old('allocation', 'Kas umum') }}" required maxlength="80">
            <datalist id="alokasi2">@foreach (config('rt.cash_allocations') as $a)<option value="{{ $a }}">@endforeach</datalist></div>
        <div class="col-md-2"><label class="form-label" for="c-amt">Nominal (Rp)</label><input id="c-amt" type="number" name="amount" min="1" class="form-control" value="{{ old('amount') }}" required></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Simpan</button></div>
        <div class="col-12"><input name="description" class="form-control" value="{{ old('description') }}" placeholder="Keterangan, mis. beli semen untuk saung" maxlength="200" aria-label="Keterangan"></div>
    </form>
    <div class="small text-secondary mt-2">Pengeluaran baru berstatus <em>diajukan</em> dan baru berlaku setelah diperiksa Wakil Ketua dan disetujui Ketua. Pemasukan langsung berlaku.</div>
</div>
@endcan

<div class="panel table-responsive" id="mutasi"><h2 class="h5 mb-3">Mutasi {{ $month ? $bulan[$month].' ' : '' }}{{ $year }}</h2>
    <table class="table align-middle mb-0"><thead><tr><th>Tanggal</th><th>Keterangan</th><th>Alokasi</th><th class="text-end">Nominal</th>@if (auth()->user()->isBoard())<th>Status</th>@endif<th></th></tr></thead><tbody>
    @forelse ($transactions as $t)
        <tr>
            <td class="text-nowrap">{{ $t->date->translatedFormat('j M Y') }}</td>
            <td>{{ $t->label() }}<div class="small text-secondary">{{ $t->category }}</div></td>
            <td>{{ $t->allocation }}</td>
            <td class="text-end text-nowrap {{ $t->type === 'masuk' ? 'text-success' : 'text-danger' }}">{{ $t->type === 'masuk' ? '+' : '-' }}{{ rupiah($t->amount) }}</td>
            @if (auth()->user()->isBoard())<td>@include('finance.partials.status', ['status' => $t->status])</td>@endif
            <td class="text-end">@can('catat-kas')@if (! $t->bill_id && ($t->status !== 'disetujui' || auth()->user()->isAdmin()))<form method="post" action="{{ route('finance.cash.destroy', $t) }}" onsubmit="return confirm('Hapus catatan ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger">Hapus</button></form>@endif @endcan</td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-secondary">Belum ada transaksi pada periode ini.</td></tr>
    @endforelse
    </tbody></table>
    <div class="mt-3">{{ $transactions->links() }}</div>
</div>
@endsection
