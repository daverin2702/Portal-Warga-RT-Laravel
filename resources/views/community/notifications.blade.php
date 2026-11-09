@extends('layouts.app', ['title' => 'Notifikasi', 'subtitle' => 'Semua pemberitahuan untuk akun Anda.'])

@section('content')
@php($ikon = ['darurat' => 'bi-exclamation-octagon-fill text-danger', 'persetujuan' => 'bi-check2-square text-primary', 'tagihan' => 'bi-wallet2 text-warning', 'pengumuman' => 'bi-megaphone text-info', 'aduan' => 'bi-chat-left-text text-secondary'])
<div class="panel p-0">
    @forelse ($notifications as $n)
        <a href="{{ $n->data['url'] ?? '#' }}" class="notif-item {{ $n->read_at ? '' : 'baru' }}">
            <i class="bi {{ $ikon[$n->data['kind'] ?? ''] ?? 'bi-info-circle text-secondary' }}"></i>
            <div><div class="fw-semibold">{{ $n->data['title'] ?? '' }}</div>
                @if (! empty($n->data['body']))<div class="small text-secondary">{{ $n->data['body'] }}</div>@endif
                <div class="small text-secondary">{{ $n->created_at->translatedFormat('d M Y, H:i') }} &middot; {{ $n->created_at->diffForHumans() }}</div></div>
        </a>
    @empty
        <div class="p-4 text-secondary">Belum ada notifikasi.</div>
    @endforelse
</div>
<div class="mt-3">{{ $notifications->links() }}</div>
@endsection
