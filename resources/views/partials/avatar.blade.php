{{-- $url, $name, $size --}}
@if ($url)
    <img src="{{ $url }}" alt="" class="rounded-circle flex-shrink-0" style="width:{{ $size ?? 36 }}px;height:{{ $size ?? 36 }}px;object-fit:cover">
@else
    <span class="rounded-circle flex-shrink-0 fw-bold" style="width:{{ $size ?? 36 }}px;height:{{ $size ?? 36 }}px;display:inline-grid;place-items:center;background:var(--brand-l);color:var(--brand-d);font-size:{{ round(($size ?? 36) * 0.36) }}px">{{ initials($name) }}</span>
@endif
