{{-- Formulir unggah bukti bayar untuk satu tagihan ($bill) --}}
<form method="post" action="{{ route('finance.bills.proof.store', $bill) }}" enctype="multipart/form-data" class="row g-2 align-items-end">@csrf
    <div class="col-md-4"><label class="form-label small">Metode</label>
        <select name="payment_method" class="form-select form-select-sm">@foreach (config('rt.payment_methods') as $m)<option>{{ $m }}</option>@endforeach</select></div>
    <div class="col-md-5"><label class="form-label small">Bukti bayar (foto/PDF, maks. 3 MB)</label>
        <input type="file" name="proof" accept="image/*,.pdf" class="form-control form-control-sm" required></div>
    <div class="col-md-3"><button class="btn btn-sm btn-primary w-100">Kirim bukti</button></div>
</form>
