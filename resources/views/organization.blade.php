@extends('layouts.app', ['title' => 'Struktur Pengurus', 'subtitle' => rt_name().' · '.$term])

@php
    $node = function (string $key, string $class) use ($holders) {
        return view('partials.org-node', ['key' => $key, 'class' => $class, 'people' => $holders->get($key, collect())])->render();
    };
@endphp

@section('content')
<div class="panel"><div class="org">
    <div class="org-core">
        {!! $node('ketua', 'n-ketua') !!}<div class="gap"></div>
        {!! $node('wakil', 'n-wakil') !!}<div class="gap"></div>
        <div class="org-mid">
            <div class="org-stack">{!! $node('sekretaris', 'n-sek') !!}{!! $node('wakil_sekretaris', 'n-wsek') !!}</div>
            {!! $node('bendahara', 'n-ben') !!}
        </div>
    </div>
    <div class="org-seksi lima">
        <div class="seksi-col">{!! $node('seksi_pembangunan', 's1') !!}</div>
        <div class="seksi-col">{!! $node('seksi_keamanan', 's2') !!}{!! $node('tim_keamanan', 'org-tim t2') !!}</div>
        <div class="seksi-col">{!! $node('seksi_kebersihan', 's5') !!}{!! $node('tim_kebersihan', 'org-tim t5') !!}</div>
        <div class="seksi-col">{!! $node('seksi_pkk', 's3') !!}</div>
        <div class="seksi-col">{!! $node('seksi_pemuda', 's4') !!}</div>
    </div>
</div>
@can('admin')<div class="text-center mt-4 no-print"><a href="{{ route('admin.users.index') }}" class="btn btn-light"><i class="bi bi-sliders me-1"></i>Atur jabatan di Akun Pengguna</a></div>@endcan
</div>
@endsection
