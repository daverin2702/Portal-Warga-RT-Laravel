@use('App\Models\Complaint')
@extends('layouts.app', ['title' => 'Aduan & Aspirasi', 'subtitle' => 'Laporkan masalah lingkungan atau sampaikan usulan, lalu pantau penanganannya.'])

@section('content')
@if ($canSubmit)
<div class="panel mb-4"><h2 class="h5 mb-3">Kirim aduan</h2>
    <form method="post" action="{{ route('complaints.store') }}" enctype="multipart/form-data" class="row g-2">
        @csrf
        <div class="col-md-4"><label class="form-label" for="category">Kategori *</label><select id="category" name="category" class="form-select">
            @foreach (Complaint::CATEGORIES as $k)<option @selected(old('category') === $k)>{{ $k }}</option>@endforeach</select></div>
        <div class="col-md-8"><label class="form-label" for="title">Judul singkat *</label><input id="title" name="title" class="form-control" value="{{ old('title') }}" placeholder="mis. Lampu jalan depan Blok B mati" required maxlength="150"></div>
        <div class="col-12"><label class="form-label" for="description">Uraian *</label><textarea id="description" name="description" rows="3" class="form-control" required maxlength="3000">{{ old('description') }}</textarea></div>
        <div class="col-md-6"><label class="form-label" for="photo">Foto (opsional, maks. 4 MB)</label><input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="form-control"></div>
        <div class="col-12"><button class="btn btn-primary"><i class="bi bi-send"></i> Kirim aduan</button></div>
    </form>
</div>
@endif

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('complaints.index') }}" class="btn btn-sm {{ $status ? 'btn-light' : 'btn-primary' }}">Semua</a>
    @foreach (Complaint::STATUS as $k => $v)
        <a href="{{ route('complaints.index', ['status' => $k]) }}" class="btn btn-sm {{ $status === $k ? 'btn-primary' : 'btn-light' }}">{{ $v }}</a>
    @endforeach
</div>

@php($warna = ['baru' => 'danger', 'diproses' => 'warning', 'selesai' => 'success'])
@forelse ($complaints as $c)
    <div class="panel mb-3">
        <div class="d-flex flex-wrap justify-content-between gap-2">
            <div>
                <span class="badge text-bg-{{ $warna[$c->status] ?? 'secondary' }}">{{ Complaint::STATUS[$c->status] ?? $c->status }}</span>
                <span class="badge text-bg-light border">{{ $c->category }}</span>
                <h2 class="h6 mt-2 mb-1">{{ $c->title }}</h2>
                <div class="small text-secondary">
                    @if (auth()->user()->isBoard()) {{ $c->household?->head_name ?? '-' }} &middot; {{ $c->household?->houseLabel() }} &middot; @endif
                    {{ $c->created_at->translatedFormat('d M Y, H:i') }}
                </div>
            </div>
            @if ($c->photo_path)<a href="{{ route('complaints.photo', $c) }}" target="_blank" class="btn btn-sm btn-light align-self-start"><i class="bi bi-image"></i> Foto</a>@endif
        </div>
        <p class="mt-2 mb-2" style="white-space:pre-line">{{ $c->description }}</p>
        @if ($c->response)
            <div class="alert alert-light border small mb-2"><strong>Tanggapan{{ $c->handler ? ' '.$c->handler->name : '' }}:</strong> {{ $c->response }}</div>
        @endif
        @can('tangani-aduan')
            <form method="post" action="{{ route('complaints.update', $c) }}" class="row g-2 align-items-end">
                @csrf @method('PUT')
                <div class="col-md-3"><select name="status" class="form-select form-select-sm" aria-label="Status">
                    @foreach (Complaint::STATUS as $k => $v)<option value="{{ $k }}" @selected($c->status === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="col-md-7"><input name="response" class="form-control form-control-sm" value="{{ $c->response }}" maxlength="300" placeholder="Tanggapan untuk warga" aria-label="Tanggapan"></div>
                <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Perbarui</button></div>
            </form>
        @endcan
    </div>
@empty
    <div class="panel text-secondary">{{ $canSubmit ? 'Belum ada aduan dari keluarga Anda.' : 'Belum ada aduan.' }}</div>
@endforelse
{{ $complaints->links() }}
@endsection
