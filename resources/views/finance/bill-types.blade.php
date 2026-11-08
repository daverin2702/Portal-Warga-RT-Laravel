@extends('layouts.app', ['title' => 'Jenis Tagihan', 'subtitle' => 'Jenis bertanda wajib ikut terbit saat Bendahara menekan "Terbitkan tagihan wajib".'])

@section('content')
<div class="panel mb-4"><h2 class="h5 mb-3">{{ $edit->exists ? 'Ubah jenis tagihan' : 'Tambah jenis tagihan' }}</h2>
    <form method="post" action="{{ $edit->exists ? route('finance.types.update', $edit) : route('finance.types.store') }}" class="row g-2">
        @csrf @if ($edit->exists) @method('PUT') @endif
        <div class="col-md-3"><label class="form-label" for="name">Nama *</label><input id="name" name="name" class="form-control" value="{{ old('name', $edit->name) }}" placeholder="mis. Iuran Sampah" required maxlength="60"></div>
        <div class="col-md-2"><label class="form-label" for="amount">Nominal bawaan (Rp) *</label><input id="amount" type="number" name="amount" min="0" class="form-control" value="{{ old('amount', $edit->amount) }}" required></div>
        <div class="col-md-3"><label class="form-label" for="allocation">Masuk ke alokasi kas *</label><input id="allocation" name="allocation" list="alokasi" class="form-control" value="{{ old('allocation', $edit->allocation) }}" required maxlength="80">
            <datalist id="alokasi">@foreach (config('rt.cash_allocations') as $a)<option value="{{ $a }}">@endforeach</datalist></div>
        <div class="col-md-4"><label class="form-label" for="description">Keterangan</label><input id="description" name="description" class="form-control" value="{{ old('description', $edit->description) }}" maxlength="150"></div>
        <div class="col-12"><label class="form-check"><input type="checkbox" class="form-check-input" name="mandatory" value="1" @checked(old('mandatory', $edit->mandatory))> Wajib (ditagihkan ke semua keluarga setiap periode)</label></div>
        <div class="col-12"><button class="btn btn-primary">Simpan</button>@if ($edit->exists) <a href="{{ route('finance.types.index') }}" class="btn btn-link">Batal</a>@endif</div>
    </form>
</div>
<div class="panel table-responsive">
    <table class="table align-middle mb-0"><thead><tr><th>Jenis</th><th class="text-end">Nominal</th><th>Alokasi</th><th>Sifat</th><th>Status</th><th>Tagihan terbit</th><th></th></tr></thead><tbody>
    @forelse ($types as $t)
        <tr class="{{ $t->active ? '' : 'text-secondary' }}">
            <td><strong>{{ $t->name }}</strong><div class="small text-secondary">{{ $t->description }}</div></td>
            <td class="text-end">{{ rupiah($t->amount) }}</td>
            <td>{{ $t->allocation }}</td>
            <td>@if ($t->mandatory)<span class="badge text-bg-primary">Wajib</span>@else<span class="badge text-bg-light border">Opsional</span>@endif</td>
            <td>@if ($t->active)<span class="badge text-bg-success">Aktif</span>@else<span class="badge text-bg-secondary">Nonaktif</span>@endif</td>
            <td>{{ $t->bills_count }}</td>
            <td class="text-nowrap text-end">
                <a class="btn btn-sm btn-light" href="{{ route('finance.types.index', ['ubah' => $t->id]) }}">Ubah</a>
                <form method="post" action="{{ route('finance.types.toggle', $t) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-light">{{ $t->active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-secondary">Belum ada jenis tagihan. Jalankan <code>php artisan db:seed --class=BillTypeSeeder</code> untuk IPL dan Kas RT bawaan.</td></tr>
    @endforelse
    </tbody></table>
</div>
@endsection
