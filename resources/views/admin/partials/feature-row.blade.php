<div class="fitur-row">
    <div class="row g-2 align-items-center">
        <div class="col-auto"><div class="ficon"><i class="bi {{ $f['icon'] ?? 'bi-star' }}"></i></div></div>
        <div class="col-md-3"><select name="features[{{ $i }}][icon]" class="form-select form-select-sm" aria-label="Ikon">
            @foreach (config('rt.feature_icons') as $icon)<option value="{{ $icon }}" @selected(($f['icon'] ?? '') === $icon)>{{ \Illuminate\Support\Str::after($icon, 'bi-') }}</option>@endforeach
        </select></div>
        <div class="col-md"><input name="features[{{ $i }}][title]" class="form-control form-control-sm" value="{{ $f['title'] ?? '' }}" placeholder="Judul" maxlength="60"></div>
        <div class="col-auto"><button type="button" class="btn btn-sm text-danger" data-hapus>Hapus</button></div>
        <div class="col-12"><textarea name="features[{{ $i }}][text]" rows="2" class="form-control form-control-sm" placeholder="Keterangan singkat" maxlength="200">{{ $f['text'] ?? '' }}</textarea></div>
    </div>
</div>
