@extends('layouts.app', ['title' => $template->exists ? 'Ubah templat' : 'Templat baru', 'subtitle' => 'Tulis langsung di kertas A4. Variabel {{...}} terisi otomatis saat surat dibuat.'])

@section('content')
<form method="post" action="{{ $template->exists ? route('letters.templates.update', $template) : route('letters.templates.store') }}" id="formTpl">
    @csrf @if ($template->exists) @method('PUT') @endif
    <input type="hidden" name="body" id="body">
    <div class="row g-3">
        <div class="col-xl-4">
            <div class="panel mb-3"><label class="form-label" for="name">Nama surat *</label>
                <input id="name" name="name" class="form-control" value="{{ old('name', $template->name) }}" required maxlength="100" placeholder="mis. Surat Keterangan Domisili"></div>
            <div class="panel mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h6 fw-bold mb-0">Input tambahan <span class="text-secondary fw-normal">(diisi saat pengajuan)</span></h2>
                    <button type="button" class="btn btn-sm btn-primary" id="addField">+ Tambah</button></div>
                <div id="fields"></div>
            </div>
            <div class="panel mb-3"><h2 class="h6 fw-bold">Variabel tersedia</h2>
                @include('letters.partials.variables')
                <div class="small fw-semibold mb-1 mt-2">Input tambahan</div><div id="chipsExtra"></div>
                <div class="alert alert-light border small mt-3 mb-0"><code>&#123;&#123;qr_ttd&#125;&#125;</code> menjadi kode QR tanda tangan Ketua (atau ruang kosong untuk tanda tangan basah) saat surat dicetak.</div>
            </div>
            <button class="btn btn-primary">Simpan templat</button> <a href="{{ route('letters.templates.index') }}" class="btn btn-link">Batal</a>
        </div>
        <div class="col-xl-8"><div class="panel p-0">@include('letters.partials.toolbar')
            <div class="a4-stage"><div class="a4">@include('letters.partials.letterhead')<div id="editor" contenteditable="true" spellcheck="false">{!! old('body', $template->body) !!}</div>@include('letters.partials.footer')</div></div>
        </div></div>
    </div>
</form>
@endsection

@push('scripts')
<script src="{{ asset('js/editor.js') }}"></script>
<script>
(function () {
    let fields = @json(array_values(old('fields', $template->fieldList())));
    const TYPES = @json(\App\Models\LetterTemplate::FIELD_TYPES), RESERVED = @json(\App\Models\LetterTemplate::RESERVED);
    const box = document.getElementById('fields'), chips = document.getElementById('chipsExtra');
    const slug = s => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').slice(0, 40);
    function chipsRefresh() {
        chips.innerHTML = '';
        fields.map(f => slug(f.label)).filter(k => k && !RESERVED.includes(k)).forEach(k => {
            const b = document.createElement('button'); b.type = 'button'; b.className = 'chip'; b.dataset.ins = '{' + '{' + k + '}' + '}'; b.textContent = k; chips.appendChild(b);
        });
        if (!chips.children.length) chips.innerHTML = '<span class="text-secondary small">Belum ada.</span>';
    }
    function draw() {
        box.innerHTML = '';
        fields.forEach((f, i) => {
            const d = document.createElement('div'); d.className = 'border rounded-3 p-2 mb-2';
            d.innerHTML = '<div class="d-flex gap-2 align-items-center"><input class="form-control form-control-sm" placeholder="Label, mis. Nama Usaha" maxlength="80"><select class="form-select form-select-sm" style="width:120px"></select></div>'
                + '<div class="d-flex justify-content-between align-items-center mt-2"><label class="small mb-0"><input type="checkbox" class="form-check-input me-1">Wajib</label><button type="button" class="btn btn-sm text-danger p-0">Hapus</button></div>'
                + '<div class="small text-secondary mt-1">Variabel: <code class="v"></code></div>';
            const lab = d.querySelector('input.form-control'), sel = d.querySelector('select'), cb = d.querySelector('input[type=checkbox]'), v = d.querySelector('.v');
            Object.entries(TYPES).forEach(([k, l]) => sel.add(new Option(l, k)));
            lab.value = f.label || ''; sel.value = f.type || 'text'; cb.checked = !!f.required;
            const tampilKunci = () => { const k = slug(lab.value); v.textContent = !k ? '(isi label dulu)' : RESERVED.includes(k) ? k + ' (nama bawaan, ganti label)' : '{' + '{' + k + '}' + '}'; };
            tampilKunci();
            lab.oninput = () => { f.label = lab.value; tampilKunci(); chipsRefresh(); };
            sel.onchange = () => f.type = sel.value; cb.onchange = () => f.required = cb.checked;
            d.querySelector('button').onclick = () => { fields.splice(i, 1); draw(); chipsRefresh(); };
            box.appendChild(d);
        });
    }
    document.getElementById('addField').onclick = () => { fields.push({ label: '', type: 'text', required: true }); draw(); };
    document.getElementById('formTpl').addEventListener('submit', e => {
        document.getElementById('body').value = document.getElementById('editor').innerHTML;
        e.target.querySelectorAll('input[name^="fields["]').forEach(x => x.remove());
        fields.forEach((f, i) => {
            if (!(f.label || '').trim()) return;
            [['label', f.label], ['type', f.type || 'text'], ['required', f.required ? 1 : 0]].forEach(([k, val]) => {
                const h = document.createElement('input'); h.type = 'hidden'; h.name = 'fields[' + i + '][' + k + ']'; h.value = val; e.target.appendChild(h);
            });
        });
    });
    draw(); chipsRefresh();
})();
</script>
@endpush
