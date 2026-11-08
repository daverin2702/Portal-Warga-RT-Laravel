@php
    $warga = ['nama', 'nik', 'no_kk', 'tempat_lahir', 'tgl_lahir', 'jenis_kelamin', 'agama', 'pekerjaan', 'alamat', 'blok', 'no_rumah', 'status_hunian', 'telp'];
    $surat = ['nomor_surat', 'tanggal_surat', 'keperluan', 'rt', 'rw', 'kelurahan', 'kecamatan', 'kota', 'nama_ketua', 'qr_ttd'];
@endphp
<div class="small text-secondary mb-2">Klik untuk menyisipkan di posisi kursor.</div>
<div class="small fw-semibold mb-1">Data warga</div>
<div>@foreach ($warga as $v)<button type="button" class="chip" data-ins="&#123;&#123;{{ $v }}&#125;&#125;">{{ $v }}</button>@endforeach</div>
<div class="small fw-semibold mb-1 mt-2">Surat &amp; wilayah</div>
<div>@foreach ($surat as $v)<button type="button" class="chip" data-ins="&#123;&#123;{{ $v }}&#125;&#125;">{{ $v }}</button>@endforeach</div>
