@extends('layouts.app', ['title' => 'Impor Data Warga', 'subtitle' => 'Unggah templat Excel untuk membuat data warga dan akun login sekaligus.'])

@section('content')
<div class="row g-3">
    <div class="col-lg-5"><div class="panel">
        <h2 class="h5">1. Unduh templat</h2>
        <p class="text-secondary">Kolom wajib: <strong>Nama</strong> dan <strong>No Rumah</strong>. Disarankan: Blok, No HP, Email. Opsional: No KK, NIK, Alamat.</p>
        <a class="btn btn-light mb-4" href="{{ route('admin.import.template') }}"><i class="bi bi-file-earmark-excel me-1"></i>Unduh templat Excel</a>
        <h2 class="h5">2. Unggah berkas</h2>
        <form method="post" action="{{ route('admin.import.store') }}" enctype="multipart/form-data">@csrf
            <input type="file" name="file" accept=".xlsx,.csv" class="form-control mb-3" required>
            <button class="btn btn-primary"><i class="bi bi-upload me-1"></i>Impor sekarang</button>
        </form>
        <div class="alert alert-light border small mt-3 mb-0">Setiap warga mendapat akun dengan kata sandi <code>{{ config('rt.import_password') }}</code> dan diminta menggantinya saat pertama masuk. Username diambil dari nomor HP, lalu email, lalu nama dan nomor rumah. Data dengan nama, blok, dan nomor rumah yang sama dilewati.</div>
    </div></div>
    <div class="col-lg-7">
        @if (! $result)
            <div class="panel text-secondary">Hasil impor akan tampil di sini.</div>
        @elseif (isset($result['error']))
            <div class="alert alert-danger">{{ $result['error'] }}</div>
        @else
            <div class="panel mb-3"><h2 class="h5"><i class="bi bi-check-circle text-success me-1"></i>{{ count($result['created']) }} akun dibuat</h2>
                @if ($result['created'])
                    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Nama</th><th>Blok</th><th>No.</th><th>Username</th></tr></thead><tbody>
                        @foreach ($result['created'] as [$name, $blk, $no, $username])<tr><td>{{ $name }}</td><td>{{ $blk }}</td><td>{{ $no }}</td><td><code>{{ $username }}</code></td></tr>@endforeach
                    </tbody></table></div>
                @endif
            </div>
            @if ($result['skipped'])
                <div class="panel"><h2 class="h5"><i class="bi bi-exclamation-triangle text-warning me-1"></i>{{ count($result['skipped']) }} baris dilewati</h2>
                    <table class="table table-sm mb-0"><thead><tr><th>Baris</th><th>Nama</th><th>Alasan</th></tr></thead><tbody>
                        @foreach ($result['skipped'] as [$line, $name, $reason])<tr><td>{{ $line }}</td><td>{{ $name }}</td><td>{{ $reason }}</td></tr>@endforeach
                    </tbody></table></div>
            @endif
        @endif
    </div>
</div>
@endsection
