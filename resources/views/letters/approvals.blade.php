@use('App\Services\LetterWorkflow')
@extends('layouts.app', ['title' => 'Persetujuan Surat', 'subtitle' => 'Sekretaris meneruskan atau membuat draf, Wakil Ketua memeriksa (checker), Ketua menandatangani (signer).'])

@section('content')
<div class="panel mb-4 table-responsive"><h2 class="h5 mb-3">Pengajuan surat warga</h2>
    <table class="table align-middle mb-0"><thead><tr><th>Pemohon</th><th>Surat</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>
    @forelse ($requests as $r)
        <tr>
            <td><strong>{{ $r->household?->head_name }}</strong><div class="small text-secondary">{{ $r->household?->houseLabel() }} · {{ $r->created_at->translatedFormat('j M Y') }}</div></td>
            <td>{{ $r->template_name }}<div class="small text-secondary">{{ $r->purpose }}</div>
                @foreach ($r->field_values ?? [] as $k => $v)@if ($v !== '')<div class="small"><span class="text-secondary">{{ str_replace('_', ' ', $k) }}:</span> {{ $v }}</div>@endif @endforeach
                @include('letters.partials.log', ['log' => $r->log])</td>
            <td>@include('letters.partials.status', ['status' => $r->status])</td>
            <td>@include('letters.partials.action', ['step' => LetterWorkflow::stepFor(LetterWorkflow::REQUEST_STEPS, $r->status, auth()->user()), 'url' => route('letters.approvals.request', $r), 'qr' => true])</td>
        </tr>
    @empty
        <tr><td colspan="4" class="text-secondary">Tidak ada pengajuan yang sedang berjalan.</td></tr>
    @endforelse
    </tbody></table>
</div>

<div class="panel table-responsive"><h2 class="h5 mb-3">Draf surat manual</h2>
    <table class="table align-middle mb-0"><thead><tr><th>Nomor</th><th>Surat</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>
    @forelse ($drafts as $d)
        <tr>
            <td class="text-nowrap">{{ $d->number }}</td>
            <td>{{ $d->title }}<div class="small text-secondary">{{ $d->household?->head_name ?? 'Tanpa data warga' }} · {{ $d->qr_signature ? 'TTD QR' : 'TTD basah' }} · <a target="_blank" href="{{ route('letters.print', $d) }}">lihat draf</a></div>
                @include('letters.partials.log', ['log' => $d->log])</td>
            <td>@include('letters.partials.status', ['status' => $d->status])</td>
            <td>@include('letters.partials.action', ['step' => LetterWorkflow::stepFor(LetterWorkflow::DRAFT_STEPS, $d->status, auth()->user()), 'url' => route('letters.approvals.draft', $d), 'qr' => $d->qr_signature])</td>
        </tr>
    @empty
        <tr><td colspan="4" class="text-secondary">Tidak ada draf yang menunggu.</td></tr>
    @endforelse
    </tbody></table>
</div>
@endsection
