@if (! empty($log))
    <div class="alur mt-1">@foreach ($log as $l){{ $l['at'] ?? '' }} · {{ $l['action'] ?? '' }} oleh {{ $l['by'] ?? '' }}@if (! empty($l['note'])): {{ $l['note'] }}@endif
@endforeach</div>
@endif
