@extends('layouts.app', ['title' => 'Profil Saya', 'subtitle' => 'Perbarui foto, data diri, dan kata sandi Anda.'])

@section('content')
<div class="row g-3">
    <div class="col-lg-5">
        <div class="panel mb-3"><h2 class="h5 mb-3">Akun &amp; foto</h2>
            <form method="post" action="{{ route('profile.account') }}" enctype="multipart/form-data">@csrf @method('PUT')
                <div class="d-flex align-items-center gap-3 mb-3">
                    @include('partials.avatar', ['url' => $user->photoUrl(), 'name' => $user->name, 'size' => 84])
                    <div class="flex-grow-1"><label class="form-label" for="photo">Ganti foto (JPG/PNG/WEBP, maks. 2 MB)</label><input type="file" id="photo" name="photo" accept="image/*" class="form-control"></div>
                </div>
                <label class="form-label" for="name">Nama</label>
                <input id="name" name="name" class="form-control mb-2" value="{{ old('name', $user->name) }}" required>
                <div class="small text-secondary mb-3">Username: <strong>{{ $user->username }}</strong> &middot; {{ $user->displayRole() }}</div>
                <button class="btn btn-primary">Simpan</button>
            </form>
        </div>
        <div class="panel {{ $user->must_change_password ? 'border border-warning' : '' }}" id="sandi"><h2 class="h5 mb-3">Ganti kata sandi</h2>
            @if ($user->must_change_password)<div class="alert alert-warning small">Anda masih memakai kata sandi bawaan. Ganti sekarang untuk membuka menu lainnya.</div>@endif
            <form method="post" action="{{ route('profile.password') }}">@csrf @method('PUT')
                <input type="password" name="current_password" class="form-control mb-2" placeholder="Kata sandi saat ini" required autocomplete="current-password">
                <input type="password" name="password" class="form-control mb-2" placeholder="Kata sandi baru (min. 8 karakter)" minlength="8" required autocomplete="new-password">
                <input type="password" name="password_confirmation" class="form-control mb-3" placeholder="Ulangi kata sandi baru" required autocomplete="new-password">
                <button class="btn btn-primary">Ganti kata sandi</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        @if ($household)
        <div class="panel"><h2 class="h5 mb-1">Data keluarga</h2>
            <p class="small text-secondary">Nama kepala keluarga, alamat, blok, dan nomor rumah dikunci karena dipakai di surat resmi. Hubungi Sekretaris jika perlu mengubahnya.</p>
            <div class="row g-2 mb-3">
                <div class="col-md-6"><label class="form-label">Kepala keluarga</label><input class="form-control" value="{{ $household->head_name }}" disabled></div>
                <div class="col-md-6"><label class="form-label">Alamat</label><input class="form-control" value="{{ $household->fullAddress() }}" disabled></div>
            </div>
            <form method="post" action="{{ route('profile.household') }}" class="row g-2">@csrf @method('PUT')
                <div class="col-md-6"><label class="form-label">No. KK</label><input name="family_card_number" inputmode="numeric" class="form-control" value="{{ old('family_card_number', $household->family_card_number) }}" @disabled($household->family_card_number)></div>
                <div class="col-md-6"><label class="form-label">NIK kepala keluarga</label><input name="head_nik" inputmode="numeric" class="form-control" value="{{ old('head_nik', $household->head_nik) }}" @disabled($household->head_nik)></div>
                <div class="col-md-6"><label class="form-label">Telepon / HP</label><input name="phone" class="form-control" value="{{ old('phone', $household->phone) }}"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="{{ old('email', $household->email) }}"></div>
                <div class="col-md-6"><label class="form-label">Tempat lahir</label><input name="birth_place" class="form-control" value="{{ old('birth_place', $household->birth_place) }}"></div>
                <div class="col-md-6"><label class="form-label">Tanggal lahir</label><input name="birth_date" type="date" class="form-control" value="{{ old('birth_date', $household->birth_date?->format('Y-m-d')) }}"></div>
                <div class="col-md-4"><label class="form-label">Jenis kelamin</label><select name="gender" class="form-select"><option value="">-</option>@foreach (['Laki-laki', 'Perempuan'] as $o)<option @selected(old('gender', $household->gender) === $o)>{{ $o }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Agama</label><select name="religion" class="form-select"><option value="">-</option>@foreach (config('rt.religions') as $o)<option @selected(old('religion', $household->religion) === $o)>{{ $o }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Pekerjaan</label><input name="occupation" class="form-control" value="{{ old('occupation', $household->occupation) }}"></div>
                <div class="col-md-4"><label class="form-label">Jumlah anggota</label><input name="members_count" type="number" min="1" max="50" class="form-control" value="{{ old('members_count', $household->members_count) }}" required></div>
                <div class="col-md-4"><label class="form-label">Status hunian</label><select name="residency_status" class="form-select">@foreach (\App\Models\Household::RESIDENCY as $k => $l)<option value="{{ $k }}" @selected(old('residency_status', $household->residency_status) === $k)>{{ $l }}</option>@endforeach</select></div>
                <div class="col-md-4"></div>
                <div class="col-md-6"><label class="form-label">Kontak darurat (nama)</label><input name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $household->emergency_contact_name) }}"></div>
                <div class="col-md-6"><label class="form-label">Kontak darurat (telepon)</label><input name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone', $household->emergency_contact_phone) }}"></div>
                <div class="col-12"><button class="btn btn-primary">Simpan data keluarga</button></div>
            </form>
        </div>
        @else
            <div class="panel text-secondary">Akun ini tidak terhubung dengan data keluarga. Administrator dapat menautkannya lewat menu Akun Pengguna.</div>
        @endif
    </div>
</div>
@endsection
