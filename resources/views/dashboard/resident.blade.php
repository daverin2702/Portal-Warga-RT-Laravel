@extends('layouts.app', ['title' => 'Halo, '.$user->name, 'subtitle' => rt_name().' · '.rt_location()])

@section('content')
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        @php($belum = $bills->where('status', 'belum'))
        <div class="row g-3 mb-3">
            <div class="col-6"><div class="stat"><div class="small text-secondary">Belum dibayar</div><div class="fs-4 fw-bold">{{ rupiah($belum->sum('amount')) }} <span class="fs-6 text-secondary">({{ $belum->count() }})</span></div></div></div>
            <div class="col-6"><div class="stat"><div class="small text-secondary">Menunggu verifikasi</div><div class="fs-4 fw-bold">{{ $bills->where('status', 'menunggu')->count() }}</div></div></div>
        </div>
        @forelse ($bills as $b)
            <div class="panel mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="fw-bold">{{ $b->name }} <span class="text-secondary fw-normal">· {{ $b->periodLabel() }}</span></div><div class="small text-secondary">{{ $b->allocation }}</div></div>
                    <div class="text-end"><div class="fs-5 fw-bold">{{ rupiah($b->amount) }}</div>@include('finance.partials.status', ['status' => $b->status])</div>
                </div>
                @if ($b->rejection_note)<div class="alert alert-danger py-2 small mt-2 mb-0">Bukti sebelumnya ditolak: {{ $b->rejection_note }}</div>@endif
                <div class="mt-3">
                    @if ($b->status === 'menunggu')
                        <div class="small text-secondary"><i class="bi bi-hourglass-split me-1"></i>Bukti diterima {{ $b->proof_uploaded_at?->translatedFormat('j M Y H:i') }} via {{ $b->payment_method }}, menunggu verifikasi Bendahara. <a href="{{ route('finance.bills.proof', $b) }}" target="_blank">Lihat bukti</a></div>
                    @else
                        @include('finance.partials.proof-form', ['bill' => $b])
                    @endif
                </div>
            </div>
        @empty
            <div class="panel text-center text-secondary py-5 mb-3"><i class="bi bi-check-circle fs-1 text-success"></i><div class="mt-2">Tidak ada tagihan yang harus dibayar. Terima kasih!</div></div>
        @endforelse
        @if ($paid->isNotEmpty())
            <div class="panel"><h2 class="h6 fw-bold">Riwayat lunas terbaru</h2>
                @foreach ($paid as $b)
                    <div class="d-flex justify-content-between border-top py-2"><span>{{ $b->name }} <span class="text-secondary">{{ $b->periodLabel() }}</span></span>
                        <span>{{ rupiah($b->amount) }} <a class="ms-2" target="_blank" href="{{ route('finance.bills.receipt', $b) }}">Struk</a></span></div>
                @endforeach
                <a href="{{ route('finance.bills.index') }}" class="d-inline-block mt-2 small">Semua tagihan</a>
            </div>
        @endif
    </div>
    <div class="col-lg-4"><div class="panel"><h2 class="h6 fw-bold"><i class="bi bi-bank me-1"></i>Cara membayar</h2>
        <p class="mb-3" style="white-space:pre-line">{{ setting('payment_info') }}</p>
        <ol class="small text-secondary ps-3 mb-0"><li>Transfer sesuai nominal tagihan.</li><li>Foto atau tangkap layar bukti transfer.</li><li>Unggah bukti pada tagihan yang dibayar.</li><li>Bendahara memverifikasi, lalu struk digital terbit.</li></ol>
        <a href="{{ route('finance.report') }}" class="d-inline-block mt-3 small">Lihat laporan keuangan RT</a>
    </div></div>
</div>

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
        <div class="panel mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h5 mb-0">Surat pengantar</h2><a href="{{ route('letters.requests.index') }}" class="btn btn-primary btn-sm"><i class="bi bi-envelope-plus me-1"></i>Ajukan surat</a></div>
            @forelse ($letterRequests as $r)
                <div class="d-flex justify-content-between align-items-center border-top py-2">
                    <div><div class="fw-semibold">{{ $r->template_name }}</div><div class="small text-secondary">{{ $r->created_at->translatedFormat('j M Y') }} · {{ $r->purpose }}</div></div>
                    <div class="text-end">@include('letters.partials.status', ['status' => $r->status])
                        @if ($r->letter?->isIssued())<a class="d-block small mt-1" target="_blank" href="{{ route('letters.print', $r->letter) }}">Cetak</a>@endif</div>
                </div>
            @empty
                <div class="text-secondary small">Belum ada pengajuan. Ajukan surat domisili, pengantar KTP/KK, SKTM, dan lainnya dari rumah.</div>
            @endforelse
        </div>
        <div class="panel text-secondary small"><i class="bi bi-info-circle me-1"></i>Pengumuman, aduan, dan notifikasi akan tersedia di dasbor ini pada tahap berikutnya.</div>
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
