@use('App\Services\CashWorkflow')
@extends('layouts.app', ['title' => 'Persetujuan Kas', 'subtitle' => 'Bendahara memposting pengeluaran, Wakil Ketua memeriksa, Ketua menyetujui.'])

@section('content')
<div class="panel table-responsive">
    <table class="table align-middle mb-0"><thead><tr><th>Tanggal</th><th>Pengeluaran</th><th class="text-end">Nominal</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>
    @forelse ($items as $t)
        @php($step = CashWorkflow::stepFor($t->status, auth()->user()))
        <tr>
            <td class="text-nowrap">{{ $t->date->translatedFormat('j M Y') }}</td>
            <td>{{ $t->label() }}<div class="small text-secondary">{{ $t->category }} · {{ $t->allocation }} · oleh {{ $t->creator?->name ?? '-' }}</div>
                @include('letters.partials.log', ['log' => $t->log])</td>
            <td class="text-end text-danger text-nowrap">{{ rupiah($t->amount) }}</td>
            <td>@include('finance.partials.status', ['status' => $t->status])</td>
            <td>
                @if ($step)
                    <form method="post" action="{{ route('finance.cash.approve', $t) }}" class="d-flex gap-1 flex-wrap">@csrf
                        <input name="note" class="form-control form-control-sm" style="max-width:200px" placeholder="Catatan (opsional)" maxlength="200">
                        <button name="action" value="{{ $step[0] }}" class="btn btn-sm btn-primary">{{ $step[3] }}</button>
                        <button name="action" value="tolak" class="btn btn-sm btn-light text-danger" onclick="return confirm('Tolak pengeluaran ini?')">Tolak</button>
                    </form>
                @else
                    <span class="small text-secondary">Menunggu pejabat berwenang</span>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-secondary">Tidak ada pengeluaran yang menunggu persetujuan.</td></tr>
    @endforelse
    </tbody></table>
</div>
@endsection
