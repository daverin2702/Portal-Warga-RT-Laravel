@extends('layouts.app', ['title' => 'Surat Pengantar', 'subtitle' => auth()->user()->isBoard() ? 'Semua pengajuan warga. Tindakan dilakukan di menu Persetujuan Surat.' : 'Ajukan surat tanpa perlu datang ke rumah pengurus.'])

@section('content')
@if ($canRequest)
    <div class="panel mb-4"><h2 class="h5 mb-3">Ajukan surat</h2>
        @if ($templates->isEmpty())
            <div class="text-secondary">Belum ada jenis surat yang tersedia. Hubungi Sekretaris RT.</div>
        @else
        <form method="post" action="{{ route('letters.requests.store') }}" class="row g-2">@csrf
            <div class="col-md-5"><label class="form-label" for="jenis">Jenis surat</label>
                <select name="letter_template_id" id="jenis" class="form-select">@foreach ($templates as $t)<option value="{{ $t->id }}" @selected(old('letter_template_id') == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
            <div class="col-md-7"><label class="form-label" for="purpose">Keperluan</label>
                <input name="purpose" id="purpose" class="form-control" value="{{ old('purpose') }}" placeholder="mis. melamar pekerjaan, pindah alamat" maxlength="250" required></div>
            <div class="col-12"><div class="row g-2" id="extra"></div></div>
            <div class="col-12"><button class="btn btn-primary">Kirim pengajuan</button></div>
        </form>
        @endif
    </div>
    @push('scripts')
    <script>
    (function () {
        const D = @json($templates->mapWithKeys(fn ($t) => [$t->id => $t->fieldList()])), lama = @json(old('fields', []));
        const sel = document.getElementById('jenis'), box = document.getElementById('extra');
        if (!sel) return;
        function gambar() {
            box.innerHTML = '';
            (D[sel.value] || []).forEach(x => {
                const c = document.createElement('div'); c.className = 'col-md-6';
                const l = document.createElement('label'); l.className = 'form-label'; l.textContent = x.label + (x.required ? ' *' : '');
                const i = document.createElement(x.type === 'textarea' ? 'textarea' : 'input');
                i.className = 'form-control'; i.name = 'fields[' + x.key + ']'; i.required = !!x.required; i.value = lama[x.key] || '';
                if (x.type === 'date' || x.type === 'number') i.type = x.type;
                l.htmlFor = i.id = 'f_' + x.key; c.append(l, i); box.appendChild(c);
            });
        }
        sel.onchange = gambar; gambar();
    })();
    </script>
    @endpush
@elseif (! auth()->user()->isBoard())
    <div class="alert alert-light border">Akun Anda belum terhubung ke data keluarga, jadi belum bisa mengajukan surat. Hubungi Sekretaris RT.</div>
@else
    <div class="mb-3"><a href="{{ route('letters.approvals.index') }}" class="btn btn-primary"><i class="bi bi-check2-square me-1"></i>Buka Persetujuan Surat</a></div>
@endif

<div class="panel table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Tanggal</th>@if (auth()->user()->isBoard())<th>Pemohon</th>@endif<th>Jenis &amp; keperluan</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($requests as $r)
            <tr>
                <td class="text-nowrap">{{ $r->created_at->translatedFormat('j M Y') }}</td>
                @if (auth()->user()->isBoard())<td>{{ $r->household?->head_name }}<div class="small text-secondary">{{ $r->household?->houseLabel() }}</div></td>@endif
                <td><strong>{{ $r->template_name }}</strong><div class="small text-secondary">{{ $r->purpose }}</div>
                    @if ($r->letter)<div class="small">No. {{ $r->letter->number }}</div>@endif
                    @if ($r->note)<div class="small">Catatan: {{ $r->note }}</div>@endif
                    @include('letters.partials.log', ['log' => $r->log])</td>
                <td>@include('letters.partials.status', ['status' => $r->status])</td>
                <td class="text-nowrap">@if ($r->letter?->isIssued())<a class="btn btn-sm btn-light" target="_blank" href="{{ route('letters.print', $r->letter) }}"><i class="bi bi-printer me-1"></i>Cetak</a>@endif</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-secondary">Belum ada pengajuan surat.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-3">{{ $requests->links() }}</div>
</div>
@endsection
