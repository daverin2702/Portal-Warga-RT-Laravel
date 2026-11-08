@extends('layouts.app', ['title' => 'Data Warga', 'subtitle' => 'Database kependudukan RT: kepala keluarga, anggota, hunian, dan kontak darurat.'])

@section('content')
<div class="panel mb-3">
    <form class="row g-2 align-items-end">
        <div class="col-md-5"><label class="form-label" for="q">Cari</label><input id="q" name="q" class="form-control" value="{{ $q }}" placeholder="Nama, No. KK, telepon, atau nomor rumah"></div>
        <div class="col-md-3"><label class="form-label" for="blok">Blok</label><select id="blok" name="blok" class="form-select"><option value="">Semua blok</option>@foreach ($blocks as $b)<option @selected($block === $b)>{{ $b }}</option>@endforeach</select></div>
        <div class="col-md-4 d-flex gap-2"><button class="btn btn-light">Tampilkan</button>
            @can('kelola-warga')<a href="{{ route('households.create') }}" class="btn btn-primary ms-auto"><i class="bi bi-plus-lg me-1"></i>Tambah keluarga</a>@endcan</div>
    </form>
</div>
<div class="panel table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Kepala keluarga</th><th>Rumah</th><th>Anggota</th><th>Hunian</th><th>Kontak darurat</th><th>Akun</th><th></th></tr></thead>
        <tbody>
        @forelse ($households as $h)
            <tr>
                <td><strong>{{ $h->head_name }}</strong><div class="small text-secondary">{{ $h->family_card_number ? 'KK '.$h->family_card_number : 'KK belum diisi' }}{{ $h->phone ? ' · '.$h->phone : '' }}</div></td>
                <td>{{ $h->houseLabel() ?: '-' }}<div class="small text-secondary">{{ $h->street }}</div></td>
                <td>{{ $h->members_count }}</td>
                <td>{{ $h->residencyLabel() }}</td>
                <td>{{ $h->emergency_contact_name ?: '-' }}<div class="small text-secondary">{{ $h->emergency_contact_phone }}</div></td>
                <td>@if ($h->users_count)<span class="badge text-bg-success">Ada</span>@else<span class="badge text-bg-light border">Belum</span>@endif</td>
                <td class="text-nowrap text-end">
                    @can('kelola-warga')
                        <a class="btn btn-sm btn-light" href="{{ route('households.edit', $h) }}">Ubah</a>
                        <form method="post" action="{{ route('households.destroy', $h) }}" class="d-inline" onsubmit="return confirm(@js('Hapus data keluarga '.$h->head_name.'?'))">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger">Hapus</button></form>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-secondary">Tidak ada data yang cocok.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-3">{{ $households->links() }}</div>
</div>
@endsection
