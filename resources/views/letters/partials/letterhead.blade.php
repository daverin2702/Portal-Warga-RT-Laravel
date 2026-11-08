@php
    $kiri = upload_url(setting('logo'));
    $kanan = upload_url(setting('logo_right'));
    $dua = $kiri || $kanan;
    $baris = letterhead_lines();
@endphp
<div class="kop">
    @if ($dua)@if ($kiri)<img src="{{ $kiri }}" alt="">@else<span class="kop-sp"></span>@endif @endif
    <div class="kop-t">
        @foreach ($baris as $i => $teks)
            @if ($teks)<div class="k{{ $i + 1 }}">{{ $teks }}</div>@endif
        @endforeach
        @if (setting('secretariat_address'))<div class="ka">Sekretariat: {{ setting('secretariat_address') }}</div>@endif
    </div>
    @if ($dua)@if ($kanan)<img src="{{ $kanan }}" alt="">@else<span class="kop-sp"></span>@endif @endif
</div>
<div class="kop-line"></div>
