@use('App\Models\Announcement')
@extends('layouts.app', ['title' => 'Pengumuman', 'subtitle' => 'Kegiatan warga, kerja bakti, jadwal siskamling, dan kabar keamanan lingkungan.'])

@section('content')
@can('kelola-pengumuman')
<div class="panel mb-4"><h2 class="h5 mb-3">Buat pengumuman</h2>
    <form method="post" action="{{ route('announcements.store') }}" class="row g-2">
        @csrf
        <div class="col-md-8"><label class="form-label" for="title">Judul *</label><input id="title" name="title" class="form-control" value="{{ old('title') }}" required maxlength="150"></div>
        <div class="col-md-4"><label class="form-label" for="category">Kategori *</label><select id="category" name="category" class="form-select">
            @foreach (Announcement::CATEGORIES as $k => $v)<option value="{{ $k }}" @selected(old('category') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label" for="body">Isi *</label><textarea id="body" name="body" rows="4" class="form-control" required maxlength="5000">{{ old('body') }}</textarea></div>
        <div class="col-12 d-flex align-items-center gap-3"><button class="btn btn-primary"><i class="bi bi-megaphone"></i> Terbitkan</button><span class="small text-secondary">Semua akun akan menerima notifikasi.</span></div>
    </form>
</div>
@endcan

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('announcements.index') }}" class="btn btn-sm {{ $category ? 'btn-light' : 'btn-primary' }}">Semua</a>
    @foreach (Announcement::CATEGORIES as $k => $v)
        <a href="{{ route('announcements.index', ['kategori' => $k]) }}" class="btn btn-sm {{ $category === $k ? 'btn-primary' : 'btn-light' }}">{{ $v }}</a>
    @endforeach
</div>

@forelse ($announcements as $a)
    <article class="panel mb-3">
        <div class="d-flex justify-content-between gap-3">
            <div>
                <span class="badge text-bg-light border mb-1">{{ Announcement::CATEGORIES[$a->category] ?? $a->category }}</span>
                <h2 class="h5 mb-1">{{ $a->title }}</h2>
                <div class="small text-secondary mb-2">{{ $a->author?->name ?? 'Pengurus' }} &middot; {{ $a->created_at->translatedFormat('d F Y, H:i') }}</div>
            </div>
            @can('kelola-pengumuman')
                <form method="post" action="{{ route('announcements.destroy', $a) }}" onsubmit="return confirm('Hapus pengumuman ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light" title="Hapus"><i class="bi bi-trash"></i></button></form>
            @endcan
        </div>
        <div style="white-space:pre-line">{{ $a->body }}</div>
    </article>
@empty
    <div class="panel text-secondary">Belum ada pengumuman.</div>
@endforelse
{{ $announcements->links() }}
@endsection
