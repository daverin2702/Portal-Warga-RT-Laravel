@if (! empty($log))
    <div class="alur mt-1">@foreach ($log as $l){{ $l['at'] ?? '' }} · {{ $l['action'] ?? '' }}@if (! empty($l['by'])) oleh {{ $l['by'] }}@endif @if (! empty($l['note'])): {{ $l['note'] }}@endif
@endforeach</div>
@endif
