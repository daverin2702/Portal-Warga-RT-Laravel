@extends('layouts.app', ['title' => 'Akun Pengguna', 'subtitle' => 'Atur akses dan jabatan. Pergantian pengurus cukup dengan mengubah jabatan.'])

@section('content')
<div class="panel mb-3">
    <form class="row g-2 align-items-end">
        <div class="col-md-5"><label class="form-label" for="q">Cari</label><input id="q" name="q" class="form-control" value="{{ $q }}" placeholder="Nama atau username"></div>
        <div class="col-md-4"><label class="form-label" for="jabatan">Jabatan</label><select id="jabatan" name="jabatan" class="form-select"><option value="">Semua</option>@foreach (config('rt.positions') as $k => $l)<option value="{{ $k }}" @selected(request('jabatan') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-md-3 d-flex gap-2"><button class="btn btn-light">Tampilkan</button><a href="{{ route('admin.users.create') }}" class="btn btn-primary ms-auto"><i class="bi bi-plus-lg me-1"></i>Akun baru</a></div>
    </form>
    <div class="small text-secondary mt-3">Ketua, Wakil, Sekretaris, Wakil Sekretaris, dan Bendahara hanya dipegang satu orang. Memberi jabatan itu ke akun lain otomatis melepasnya dari pemegang lama dan mengatur hak aksesnya.</div>
</div>
<div class="panel table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Nama</th><th>Username</th><th>Hak akses</th><th>Jabatan</th><th>Keluarga</th><th></th></tr></thead>
        <tbody>
        @foreach ($users as $a)
            <tr>
                <td><div class="d-flex align-items-center gap-2">@include('partials.avatar', ['url' => $a->photoUrl(), 'name' => $a->name, 'size' => 36])<div><strong>{{ $a->name }}</strong>@if ($a->must_change_password)<div class="small text-warning-emphasis">Belum ganti sandi</div>@endif</div></div></td>
                <td>{{ $a->username }}</td>
                <td>{{ $a->roleLabel() }}</td>
                <td>
                    <form method="post" action="{{ route('admin.users.position', $a) }}">@csrf @method('PATCH')
                        <select name="position" class="form-select form-select-sm" style="min-width:220px" onchange="this.form.submit()" aria-label="Jabatan {{ $a->name }}">
                            @foreach (config('rt.positions') as $k => $l)<option value="{{ $k }}" @selected($a->position === $k)>{{ $l }}</option>@endforeach
                        </select>
                    </form>
                </td>
                <td>{{ $a->household?->houseLabel() ?: '-' }}</td>
                <td class="text-nowrap text-end">
                    <a class="btn btn-sm btn-light" href="{{ route('admin.users.edit', $a) }}">Ubah</a>
                    @unless ($a->is(auth()->user()))
                        <form method="post" action="{{ route('admin.users.destroy', $a) }}" class="d-inline" onsubmit="return confirm(@js('Hapus akun '.$a->name.'?'))">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger">Hapus</button></form>
                    @endunless
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="mt-3">{{ $users->links() }}</div>
</div>
@endsection
