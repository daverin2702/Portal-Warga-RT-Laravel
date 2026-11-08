@foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $type)
    @if (session($key))
        <div class="alert alert-{{ $type }} alert-dismissible no-print">{{ session($key) }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>
    @endif
@endforeach
@if ($errors->any())
    <div class="alert alert-danger no-print">
        <div class="fw-semibold mb-1">Periksa kembali isian berikut:</div>
        <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
