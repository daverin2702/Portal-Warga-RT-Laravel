@extends('layouts.app', ['title' => 'Dasbor', 'subtitle' => $user->displayRole().' · '.rt_name()])

@section('content')
<div class="row g-3 mb-4">
    @foreach ([['Jumlah KK', number_format($stats['households'], 0, ',', '.'), 'households.index'], ['Total jiwa', number_format($stats['residents'], 0, ',', '.'), 'households.index'], ['Saldo kas', rupiah($stats['balance']), 'finance.cash.index'], ['Tagihan belum lunas', number_format($stats['unpaid'], 0, ',', '.'), 'finance.bills.index']] as [$label, $value, $route])
        <div class="col-6 col-lg-3"><a href="{{ route($route) }}" class="stat text-decoration-none">
            <div class="small text-secondary">{{ $label }}</div><div class="fs-4 fw-bold">{{ $value }}</div></a></div>
    @endforeach
</div>

<div class="panel mb-4">
    <h2 class="h5 mb-3"><i class="bi bi-bell me-1"></i>Perlu tindakan Anda</h2>
    @forelse ($tasks as [$label, $n, $url])
        <a href="{{ $url }}" class="d-flex justify-content-between align-items-center border-top py-2 text-decoration-none text-reset"><span>{{ $label }}</span><span class="badge text-bg-warning">{{ $n }}</span></a>
    @empty
        <div class="text-secondary">Tidak ada yang menunggu. Semua beres.</div>
    @endforelse
    <h2 class="h6 fw-bold mt-4 mb-2"><i class="bi bi-lightning-charge me-1"></i>Akses cepat</h2>
    <div class="d-flex flex-wrap gap-2">
        @can('buat-surat')<a class="btn btn-primary btn-sm" href="{{ route('letters.create') }}"><i class="bi bi-pencil-square me-1"></i>Buat surat</a>@endcan
        <a class="btn btn-light btn-sm" href="{{ route('letters.approvals.index') }}">Persetujuan surat</a>
        @can('kelola-tagihan')<a class="btn btn-primary btn-sm" href="{{ route('finance.bills.index') }}"><i class="bi bi-wallet2 me-1"></i>Terbitkan tagihan</a>@endcan
        @can('catat-kas')<a class="btn btn-primary btn-sm" href="{{ route('finance.cash.index') }}"><i class="bi bi-cash-coin me-1"></i>Posting pengeluaran</a>@endcan
        <a class="btn btn-light btn-sm" href="{{ route('finance.report') }}">Laporan keuangan</a>
        @can('kelola-warga')<a class="btn btn-primary btn-sm" href="{{ route('households.create') }}"><i class="bi bi-person-plus me-1"></i>Tambah keluarga</a>@endcan
        <a class="btn btn-light btn-sm" href="{{ route('households.index') }}">Data warga</a>
        <a class="btn btn-light btn-sm" href="{{ route('organization') }}">Struktur pengurus</a>
        @if ($user->isAdmin())
            <a class="btn btn-light btn-sm" href="{{ route('admin.import.create') }}">Impor Excel</a>
            <a class="btn btn-light btn-sm" href="{{ route('admin.settings.edit') }}">Pengaturan situs</a>
        @endif
    </div>
    <div class="small text-secondary mt-3">Modul pengumuman, aduan, notifikasi, tombol darurat, dan chat menyusul di tahap berikutnya.</div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8"><div class="panel h-100"><h2 class="h6 fw-bold">Jumlah KK per blok</h2><div class="chart-box"><canvas id="cBlok" aria-label="Grafik jumlah KK per blok" role="img"></canvas></div></div></div>
    <div class="col-lg-4"><div class="panel h-100"><h2 class="h6 fw-bold">Status hunian</h2><div class="chart-box"><canvas id="cHunian" aria-label="Grafik status hunian" role="img"></canvas></div></div></div>
</div>

<div class="panel mb-4"><h2 class="h6 fw-bold">Arus kas {{ now()->year }} (yang sudah disetujui)</h2><div class="chart-box"><canvas id="cKas" aria-label="Grafik arus kas bulanan" role="img"></canvas></div></div>

<div class="panel table-responsive"><h2 class="h5 mb-3">Keluarga terbaru</h2>
    <table class="table align-middle mb-0"><thead><tr><th>Kepala keluarga</th><th>Rumah</th><th>Anggota</th><th>Hunian</th><th>Didaftarkan</th></tr></thead><tbody>
    @forelse ($latest as $h)
        <tr><td class="fw-semibold">{{ $h->head_name }}</td><td>{{ $h->houseLabel() ?: '-' }}</td><td>{{ $h->members_count }}</td><td>{{ $h->residencyLabel() }}</td><td>{{ $h->created_at?->translatedFormat('j M Y') }}</td></tr>
    @empty
        <tr><td colspan="5" class="text-secondary">Belum ada data keluarga. @can('kelola-warga')<a href="{{ route('households.create') }}">Tambah sekarang</a>.@endcan</td></tr>
    @endforelse
    </tbody></table>
</div>
@endsection

@push('scripts')
<script src="{{ rt_asset('chart.js') }}"></script>
<script>
(function () {
    if (!window.Chart) return;
    const css = getComputedStyle(document.documentElement);
    const warna = [css.getPropertyValue('--brand').trim() || '#0E7C86', css.getPropertyValue('--accent').trim() || '#F2B705', '#2F6DB5', '#C0392B', '#6FAF3D'];
    Chart.defaults.font.family = css.getPropertyValue('--font').trim() || 'system-ui';
    const blok = @json($perBlock), hunian = @json($residency);
    new Chart(document.getElementById('cBlok'), { type: 'bar', data: { labels: Object.keys(blok), datasets: [{ label: 'KK', data: Object.values(blok), backgroundColor: warna[0], borderRadius: 6 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } } });
    new Chart(document.getElementById('cHunian'), { type: 'doughnut', data: { labels: Object.keys(hunian), datasets: [{ data: Object.values(hunian), backgroundColor: [warna[0], warna[1]] }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
    const kas = @json($cashFlow);
    new Chart(document.getElementById('cKas'), { type: 'bar', data: { labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
        datasets: [{ label: 'Pemasukan', data: kas.in, backgroundColor: warna[0], borderRadius: 6 }, { label: 'Pengeluaran', data: kas.out, backgroundColor: warna[3], borderRadius: 6 }] },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { callback: v => 'Rp ' + Number(v).toLocaleString('id-ID') } } } } });
})();
</script>
@endpush
