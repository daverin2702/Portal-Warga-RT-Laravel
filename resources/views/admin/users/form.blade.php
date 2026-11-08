@extends('layouts.app', ['title' => $user->exists ? 'Ubah akun' : 'Akun baru'])

@section('content')
<form method="post" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" enctype="multipart/form-data" class="panel row g-3 mx-0" style="max-width:900px">
    @csrf @if ($user->exists) @method('PUT') @endif
    <div class="col-md-6"><label class="form-label">Nama lengkap *</label><input name="name" class="form-control" value="{{ old('name', $user->name) }}" required></div>
    <div class="col-md-6"><label class="form-label">Username{{ $user->exists ? '' : ' *' }}</label>
        @if ($user->exists)<input class="form-control" value="{{ $user->username }}" disabled>
        @else<input name="username" class="form-control" value="{{ old('username') }}" required pattern="[A-Za-z0-9_.]+" autocomplete="off">@endif</div>
    <div class="col-md-6"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}"></div>
    <div class="col-md-6"><label class="form-label">Kata sandi{{ $user->exists ? ' (kosongkan jika tidak diganti)' : ' *' }}</label><input name="password" type="password" class="form-control" minlength="8" @required(! $user->exists) autocomplete="new-password">
        <div class="form-text">Pengguna akan diminta menggantinya saat masuk.</div></div>
    <div class="col-md-4"><label class="form-label">Hak akses *</label>
        <select name="role" class="form-select" @disabled($user->is(auth()->user()))>@foreach (config('rt.roles') as $k => $l)<option value="{{ $k }}" @selected(old('role', $user->role) === $k)>{{ $l }}</option>@endforeach</select>
        @if ($user->is(auth()->user()))<input type="hidden" name="role" value="{{ $user->role }}"><div class="form-text">Hak akses akun Anda sendiri tidak bisa diubah.</div>@endif</div>
    <div class="col-md-8"><label class="form-label">Jabatan *</label><select name="position" class="form-select">@foreach (config('rt.positions') as $k => $l)<option value="{{ $k }}" @selected(old('position', $user->position) === $k)>{{ $l }}</option>@endforeach</select>
        <div class="form-text">Jabatan inti otomatis memberi akses pengurus.</div></div>
    <div class="col-md-6"><label class="form-label">Keluarga terkait</label><select name="household_id" class="form-select"><option value="">-</option>
        @foreach ($households as $h)<option value="{{ $h->id }}" @selected((string) old('household_id', $user->household_id) === (string) $h->id)>{{ $h->head_name }}{{ $h->houseLabel() ? ' ('.$h->houseLabel().')' : '' }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Foto (tampil di struktur pengurus)</label><input type="file" name="photo" accept="image/*" class="form-control"></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-primary">{{ $user->exists ? 'Simpan perubahan' : 'Buat akun' }}</button><a href="{{ route('admin.users.index') }}" class="btn btn-link">Batal</a></div>
</form>
@endsection
