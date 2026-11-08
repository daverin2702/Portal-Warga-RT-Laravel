@extends('layouts.app', ['title' => $household->exists ? 'Ubah data keluarga' : 'Tambah keluarga'])

@php
    $v = fn ($k) => old($k, $household->$k instanceof \Carbon\CarbonInterface ? $household->$k->format('Y-m-d') : $household->$k);
@endphp

@section('content')
<form method="post" action="{{ $household->exists ? route('households.update', $household) : route('households.store') }}" class="panel row g-3 mx-0">
    @csrf @if ($household->exists) @method('PUT') @endif
    <div class="col-12"><h2 class="h6 fw-bold mb-0">Kepala keluarga</h2></div>
    <div class="col-md-6"><label class="form-label">Nama kepala keluarga *</label><input name="head_name" class="form-control" value="{{ $v('head_name') }}" required></div>
    <div class="col-md-3"><label class="form-label">No. KK</label><input name="family_card_number" inputmode="numeric" class="form-control" value="{{ $v('family_card_number') }}"></div>
    <div class="col-md-3"><label class="form-label">NIK</label><input name="head_nik" inputmode="numeric" class="form-control" value="{{ $v('head_nik') }}"></div>
    <div class="col-md-3"><label class="form-label">Tempat lahir</label><input name="birth_place" class="form-control" value="{{ $v('birth_place') }}"></div>
    <div class="col-md-3"><label class="form-label">Tanggal lahir</label><input name="birth_date" type="date" class="form-control" value="{{ $v('birth_date') }}"></div>
    <div class="col-md-2"><label class="form-label">Jenis kelamin</label><select name="gender" class="form-select"><option value="">-</option>@foreach (['Laki-laki', 'Perempuan'] as $o)<option @selected($v('gender') === $o)>{{ $o }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label">Agama</label><select name="religion" class="form-select"><option value="">-</option>@foreach (config('rt.religions') as $o)<option @selected($v('religion') === $o)>{{ $o }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label">Pekerjaan</label><input name="occupation" class="form-control" value="{{ $v('occupation') }}"></div>

    <div class="col-12 mt-4"><h2 class="h6 fw-bold mb-0">Tempat tinggal</h2></div>
    <div class="col-md-6"><label class="form-label">Alamat / jalan</label><input name="street" class="form-control" value="{{ $v('street') }}" placeholder="Jl. Mawar"></div>
    <div class="col-md-2"><label class="form-label">Blok</label><input name="block" class="form-control" value="{{ $v('block') }}" placeholder="A"></div>
    <div class="col-md-2"><label class="form-label">No. rumah *</label><input name="house_number" class="form-control" value="{{ $v('house_number') }}" required></div>
    <div class="col-md-2"><label class="form-label">Jumlah anggota *</label><input name="members_count" type="number" min="1" max="50" class="form-control" value="{{ $v('members_count') }}" required></div>
    <div class="col-md-3"><label class="form-label">Status hunian *</label><select name="residency_status" class="form-select">@foreach (\App\Models\Household::RESIDENCY as $k => $l)<option value="{{ $k }}" @selected($v('residency_status') === $k)>{{ $l }}</option>@endforeach</select></div>

    <div class="col-12 mt-4"><h2 class="h6 fw-bold mb-0">Kontak</h2></div>
    <div class="col-md-3"><label class="form-label">Telepon / HP</label><input name="phone" class="form-control" value="{{ $v('phone') }}"></div>
    <div class="col-md-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="{{ $v('email') }}"></div>
    <div class="col-md-3"><label class="form-label">Kontak darurat (nama)</label><input name="emergency_contact_name" class="form-control" value="{{ $v('emergency_contact_name') }}"></div>
    <div class="col-md-3"><label class="form-label">Kontak darurat (telepon)</label><input name="emergency_contact_phone" class="form-control" value="{{ $v('emergency_contact_phone') }}"></div>

    <div class="col-12 d-flex gap-2 mt-4"><button class="btn btn-primary">Simpan</button><a href="{{ route('households.index') }}" class="btn btn-link">Batal</a></div>
</form>
@endsection
