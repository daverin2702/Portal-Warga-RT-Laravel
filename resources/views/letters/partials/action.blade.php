{{-- Tombol tindakan persetujuan. $step = [aksi, wewenang, status_baru, label] atau null; $url; $qr (pilihan awal TTD) --}}
@if ($step)
    <form method="post" action="{{ $url }}" class="d-flex gap-1 flex-wrap">@csrf
        @if ($step[0] === 'setujui')
            <select name="signature" class="form-select form-select-sm" style="max-width:190px" aria-label="Jenis tanda tangan">
                <option value="qr" @selected($qr ?? true)>TTD digital (QR)</option>
                <option value="basah" @selected(! ($qr ?? true))>TTD basah (tanpa QR)</option>
            </select>
        @endif
        <input name="note" class="form-control form-control-sm" style="max-width:200px" placeholder="Catatan (opsional)" maxlength="250">
        <button name="action" value="{{ $step[0] }}" class="btn btn-sm btn-primary">{{ $step[3] }}</button>
        <button name="action" value="tolak" class="btn btn-sm btn-light text-danger" onclick="return confirm('Tolak permintaan ini?')">Tolak</button>
    </form>
@else
    <span class="small text-secondary">Menunggu pejabat berwenang</span>
@endif
