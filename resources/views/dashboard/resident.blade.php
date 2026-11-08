@extends('layouts.app', ['title' => 'Halo, '.$user->name, 'subtitle' => rt_name().' · '.rt_location()])

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel mb-3">
            <h2 class="h5 mb-3">Data keluarga Anda</h2>
            @if ($household)
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-secondary fw-normal">Kepala keluarga</dt><dd class="col-sm-8 fw-semibold">{{ $household->head_name }}</dd>
                    <dt class="col-sm-4 text-secondary fw-normal">Alamat</dt><dd class="col-sm-8">{{ $household->fullAddress() ?: '-' }}</dd>
                    <dt class="col-sm-4 text-secondary fw-normal">Anggota keluarga</dt><dd class="col-sm-8">{{ $household->members_count }} orang</dd>
                    <dt class="col-sm-4 text-secondary fw-normal">Status hunian</dt><dd class="col-sm-8">{{ $household->residencyLabel() }}</dd>
                    <dt class="col-sm-4 text-secondary fw-normal">Kontak darurat</dt><dd class="col-sm-8">{{ $household->emergency_contact_name ?: '-' }} {{ $household->emergency_contact_phone }}</dd>
                </dl>
                <a href="{{ route('profile.edit') }}" class="btn btn-light btn-sm mt-3"><i class="bi bi-pencil me-1"></i>Perbarui data</a>
            @else
                <p class="text-secondary mb-0">Akun Anda belum terhubung ke data keluarga. Hubungi Sekretaris RT.</p>
            @endif
        </div>
        <div class="panel text-secondary small"><i class="bi bi-info-circle me-1"></i>Tagihan iuran, surat pengantar, pengumuman, dan aduan akan tersedia di dasbor ini pada tahap berikutnya.</div>
    </div>
    <div class="col-lg-5"><div class="panel">
        <h2 class="h5 mb-3">Pengurus inti</h2>
        @forelse ($board as $b)
            <div class="d-flex align-items-center gap-2 border-top py-2">
                @include('partials.avatar', ['url' => $b->photoUrl(), 'name' => $b->name, 'size' => 36])
                <div><div class="fw-semibold">{{ $b->name }}</div><div class="small text-secondary">{{ position_label($b->position) }}</div></div>
            </div>
        @empty
            <div class="text-secondary small">Belum ada pengurus yang ditetapkan.</div>
        @endforelse
        <a href="{{ route('organization') }}" class="d-inline-block mt-2 small">Lihat struktur lengkap</a>
    </div></div>
</div>
@endsection
