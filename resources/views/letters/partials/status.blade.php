@php
    $warna = ['diajukan' => 'warning', 'draf' => 'warning', 'diteruskan' => 'info', 'diperiksa' => 'primary', 'disetujui' => 'success', 'terbit' => 'success', 'ditolak' => 'danger'];
@endphp
<span class="badge text-bg-{{ $warna[$status] ?? 'secondary' }}">{{ ucfirst($status) }}</span>
