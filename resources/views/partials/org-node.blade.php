<div class="org-node {{ $class }}">
    <div class="org-title">{{ position_label($key) }}</div>
    @forelse ($people as $p)
        <div class="org-person">
            @if ($p->photoUrl())<img src="{{ $p->photoUrl() }}" alt="">@else<span class="ini">{{ $p->initials() }}</span>@endif
            <span>{{ $p->name }}</span>
        </div>
    @empty
        <div class="vacant">Belum ditetapkan</div>
    @endforelse
</div>
