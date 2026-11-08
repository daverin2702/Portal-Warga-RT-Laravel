@extends('layouts.app', ['title' => 'Tagihan & Pembayaran', 'subtitle' => auth()->user()->can('kelola-tagihan') ? 'Terbitkan tagihan IPL, Kas RT, dan lainnya, lalu verifikasi bukti bayar warga.' : (auth()->user()->isBoard() ? 'Tagihan seluruh warga.' : 'Tagihan keluarga Anda. Pembayaran masih manual: transfer lalu unggah bukti.')])

@section('content')
@php($u = auth()->user())

@if ($u->isBoard())
<div class="row g-3 mb-4">
    @foreach (['belum' => 'Belum dibayar', 'menunggu' => 'Menunggu verifikasi', 'lunas' => 'Lunas'] as $k => $label)
        <div class="col-md-4"><a href="{{ route('finance.bills.index', ['status' => $k]) }}" class="stat text-decoration-none">
            <div class="small text-secondary">{{ $label }}</div><div class="fs-4 fw-bold">{{ $summary[$k]->n ?? 0 }} <span class="fs-6 text-secondary">tagihan · {{ rupiah($summary[$k]->total ?? 0) }}</span></div></a></div>
    @endforeach
</div>
@endif

@can('kelola-tagihan')
@php($wajib = $types->where('mandatory', true))
<div class="row g-3 mb-4">
    <div class="col-lg-5"><div class="panel h-100"><h2 class="h5 mb-1">Terbitkan tagihan wajib</h2>
        <p class="small text-secondary">Satu klik menagih semua keluarga untuk setiap jenis tagihan wajib. Tagihan yang sudah ada pada periode yang sama dilewati.</p>
        @if ($wajib->isNotEmpty())
            <ul class="list-unstyled small mb-3">@foreach ($wajib as $t)<li class="d-flex justify-content-between border-top py-1"><span>{{ $t->name }}</span><strong>{{ rupiah($t->amount) }}</strong></li>@endforeach</ul>
            <form method="post" action="{{ route('finance.bills.mandatory') }}" class="d-flex gap-2" onsubmit="return confirm('Terbitkan semua tagihan wajib untuk seluruh keluarga?')">@csrf
                <input type="month" name="period" class="form-control" value="{{ now()->format('Y-m') }}" required aria-label="Periode">
                <button class="btn btn-primary text-nowrap">Terbitkan</button></form>
        @else
            <div class="small text-secondary">Belum ada jenis tagihan wajib. <a href="{{ route('finance.types.index') }}">Atur jenis tagihan</a>.</div>
        @endif
    </div></div>
    <div class="col-lg-7"><div class="panel h-100"><h2 class="h5 mb-1">Tagihan lain</h2>
        <p class="small text-secondary">Untuk tagihan di luar yang wajib, atau menagih satu keluarga saja. <a href="{{ route('finance.types.index') }}">Tambah jenis baru</a>.</p>
        <form method="post" action="{{ route('finance.bills.store') }}" class="row g-2">@csrf
            <div class="col-md-5"><label class="form-label" for="jenis">Jenis</label>
                <select name="bill_type_id" id="jenis" class="form-select" required>@foreach ($types as $t)<option value="{{ $t->id }}" data-nominal="{{ $t->amount }}">{{ $t->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label" for="periode">Periode</label><input type="month" name="period" id="periode" class="form-control" value="{{ now()->format('Y-m') }}" required></div>
            <div class="col-md-4"><label class="form-label" for="nominal">Nominal (Rp)</label><input type="number" name="amount" id="nominal" min="1000" class="form-control" required></div>
            <div class="col-md-8"><label class="form-label" for="kk">Ditagihkan ke</label>
                <select name="household_id" id="kk" class="form-select"><option value="">Semua keluarga</option>
                    @foreach ($households as $h)<option value="{{ $h->id }}">{{ trim($h->houseLabel().' - '.$h->head_name, ' -') }}</option>@endforeach</select></div>
            <div class="col-md-4 d-flex align-items-end"><button class="btn btn-outline-primary w-100">Buat tagihan</button></div>
        </form>
    </div></div>
</div>
@push('scripts')
<script>
(function () { const j = document.getElementById('jenis'), n = document.getElementById('nominal'); if (!j) return;
    const set = () => { const o = j.selectedOptions[0]; if (o && +o.dataset.nominal) n.value = o.dataset.nominal; }; j.onchange = set; set(); })();
</script>
@endpush
@endcan

<div class="panel mb-3"><form class="row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label" for="f-status">Status</label><select id="f-status" name="status" class="form-select"><option value="">Semua</option>@foreach (\App\Models\Bill::STATUS as $k => $l)<option value="{{ $k }}" @selected($status === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label" for="f-periode">Periode</label><input type="month" id="f-periode" name="periode" class="form-control" value="{{ $period }}"></div>
    <div class="col-md-4"><button class="btn btn-light">Tampilkan</button></div>
</form></div>

<div class="panel table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Periode</th>@if ($u->isBoard())<th>Keluarga</th>@endif<th>Tagihan</th><th class="text-end">Nominal</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($bills as $b)
            <tr>
                <td class="text-nowrap">{{ $b->periodLabel() }}</td>
                @if ($u->isBoard())<td>{{ $b->household?->head_name }}<div class="small text-secondary">{{ $b->household?->houseLabel() }}</div></td>@endif
                <td>{{ $b->name }}<div class="small text-secondary">{{ $b->allocation }}</div></td>
                <td class="text-end text-nowrap">{{ rupiah($b->amount) }}</td>
                <td>@include('finance.partials.status', ['status' => $b->status])
                    @if ($b->payment_method)<div class="small text-secondary">{{ $b->payment_method }}</div>@endif
                    @if ($b->status === 'belum' && $b->rejection_note)<div class="small text-danger">Bukti ditolak: {{ $b->rejection_note }}</div>@endif</td>
                <td style="min-width:240px">
                    @if ($b->status === 'lunas')
                        <a class="btn btn-sm btn-light" target="_blank" href="{{ route('finance.bills.receipt', $b) }}"><i class="bi bi-receipt me-1"></i>Struk</a>
                    @elseif ($b->status === 'menunggu')
                        <a class="btn btn-sm btn-light" target="_blank" href="{{ route('finance.bills.proof', $b) }}">Lihat bukti</a>
                        @can('verifikasi-bayar')
                            <form method="post" action="{{ route('finance.bills.accept', $b) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Terima</button></form>
                            <form method="post" action="{{ route('finance.bills.reject', $b) }}" class="d-inline-flex gap-1 mt-1">@csrf<input name="note" class="form-control form-control-sm" style="width:140px" placeholder="Alasan tolak" maxlength="200"><button class="btn btn-sm btn-light text-danger">Tolak</button></form>
                        @else
                            <span class="small text-secondary d-block mt-1">Menunggu Bendahara</span>
                        @endcan
                    @elseif ($u->isBoard())
                        @can('verifikasi-bayar')<form method="post" action="{{ route('finance.bills.cash', $b) }}" class="d-inline" onsubmit="return confirm('Tandai lunas (bayar tunai)?')">@csrf<button class="btn btn-sm btn-light">Lunas tunai</button></form>@endcan
                        @can('kelola-tagihan')<form method="post" action="{{ route('finance.bills.destroy', $b) }}" class="d-inline" onsubmit="return confirm('Hapus tagihan ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger">Hapus</button></form>@endcan
                    @else
                        @include('finance.partials.proof-form', ['bill' => $b])
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-secondary">Tidak ada tagihan.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-3">{{ $bills->links() }}</div>
</div>
@endsection
