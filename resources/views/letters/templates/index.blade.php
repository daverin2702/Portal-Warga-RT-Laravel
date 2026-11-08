@extends('layouts.app', ['title' => 'Templat Surat', 'subtitle' => 'Susun templat sekali, pakai berulang untuk warga mana pun.'])

@section('content')
<div class="mb-3"><a href="{{ route('letters.templates.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Templat baru</a></div>
<div class="panel table-responsive">
    <table class="table align-middle mb-0"><thead><tr><th>Nama templat</th><th>Kolom tambahan</th><th></th></tr></thead><tbody>
    @forelse ($templates as $t)
        <tr>
            <td class="fw-semibold">{{ $t->name }}</td>
            <td class="text-secondary">{{ collect($t->fieldList())->pluck('label')->implode(', ') ?: '-' }}</td>
            <td class="text-nowrap text-end">
                <a class="btn btn-sm btn-light" href="{{ route('letters.templates.edit', $t) }}">Ubah</a>
                <form method="post" action="{{ route('letters.templates.duplicate', $t) }}" class="d-inline">@csrf<button class="btn btn-sm btn-light">Duplikat</button></form>
                <form method="post" action="{{ route('letters.templates.destroy', $t) }}" class="d-inline" onsubmit="return confirm(@js('Hapus templat '.$t->name.'?'))">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger">Hapus</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="3" class="text-secondary">Belum ada templat. Jalankan <code>php artisan db:seed --class=LetterTemplateSeeder</code> untuk memasang 7 templat bawaan.</td></tr>
    @endforelse
    </tbody></table>
</div>
@endsection
