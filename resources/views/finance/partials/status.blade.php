@php($warna = ['belum' => 'warning', 'menunggu' => 'info', 'lunas' => 'success', 'diajukan' => 'warning', 'diperiksa' => 'primary', 'disetujui' => 'success', 'ditolak' => 'danger'])
<span class="badge text-bg-{{ $warna[$status] ?? 'secondary' }}">{{ \App\Models\Bill::STATUS[$status] ?? ucfirst($status) }}</span>
