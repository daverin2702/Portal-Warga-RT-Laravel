@extends('layouts.app', ['title' => 'Arsip Surat', 'subtitle' => 'Semua surat beserta status persetujuan dan tautan verifikasinya.'])

@section('content')
<div class="panel mb-3"><form class="row g-2 align-items-end">
    <div class="col-md-6"><label class="form-label" for="q">Cari</label><input id="q" name="q" class="form-control" value="{{ $q }}" placeholder="Nomor, jenis surat, atau nama pemohon"></div>
    <div class="col-md-3"><label class="form-label" for="status">Status</label><select id="status" name="status" class="form-select"><option value="">Semua</option>@foreach (['draf', 'diperiksa', 'terbit', 'ditolak'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
    <div class="col-md-3 d-flex gap-2"><button class="btn btn-light">Tampilkan</button>@can('buat-surat')<a href="{{ route('letters.create') }}" class="btn btn-primary ms-auto">Buat surat</a>@endcan</div>
</form></div>
<div class="panel table-responsive">
    <table class="table align-middle mb-0"><thead><tr><th>Nomor</th><th>Jenis</th><th>Pemohon</th><th>Status</th><th>Tanggal</th><th></th></tr></thead><tbody>
    @forelse ($letters as $l)
        <tr>
            <td class="fw-semibold text-nowrap">{{ $l->number }}</td>
            <td>{{ $l->title }}</td>
            <td>{{ $l->household?->head_name ?? '-' }}</td>
            <td>@include('letters.partials.status', ['status' => $l->status])<div class="small text-secondary">{{ $l->qr_signature ? 'TTD QR' : 'TTD basah' }}</div></td>
            <td class="text-nowrap">{{ ($l->issued_at ?? $l->created_at)->translatedFormat('j M Y') }}</td>
            <td class="text-nowrap text-end">
                <a class="btn btn-sm btn-light" target="_blank" href="{{ route('letters.print', $l) }}"><i class="bi bi-printer me-1"></i>Cetak</a>
                @if ($l->isIssued())<a class="btn btn-sm btn-light" target="_blank" href="{{ route('letters.verify', $l->token) }}"><i class="bi bi-patch-check me-1"></i>Verifikasi</a>@endif
                @can('buat-surat')<form method="post" action="{{ route('letters.destroy', $l) }}" class="d-inline" onsubmit="return confirm(@js('Hapus surat '.$l->number.' dari arsip? QR-nya tidak lagi berlaku.'))">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger">Hapus</button></form>@endcan
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-secondary">Belum ada surat.</td></tr>
    @endforelse
    </tbody></table>
    <div class="mt-3">{{ $letters->links() }}</div>
</div>
@endsection
