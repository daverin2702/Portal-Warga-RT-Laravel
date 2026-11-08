@extends('layouts.app', ['title' => 'Buat Surat', 'subtitle' => 'Pilih templat atau tulis manual seperti di Word. Surat tersimpan sebagai draf sampai diperiksa Wakil dan ditandatangani Ketua.'])

@section('content')
<form method="post" action="{{ route('letters.store') }}" id="formSurat">
    @csrf
    <input type="hidden" name="body" id="body">
    <div class="row g-3">
        <div class="col-xl-4">
            <div class="panel mb-3">
                <div class="btn-group w-100 mb-3" role="group" aria-label="Mode">
                    <input type="radio" class="btn-check" name="mode" id="m1" value="templat" @checked(old('mode', 'templat') === 'templat')><label class="btn btn-outline-primary" for="m1">Dari templat</label>
                    <input type="radio" class="btn-check" name="mode" id="m2" value="manual" @checked(old('mode') === 'manual')><label class="btn btn-outline-primary" for="m2">Surat manual</label>
                </div>
                <div id="boxTpl" class="mb-2"><label class="form-label" for="tpl">Templat</label>
                    <select name="letter_template_id" id="tpl" class="form-select">@foreach ($templates as $id => $t)<option value="{{ $id }}" @selected(old('letter_template_id') == $id)>{{ $t['name'] }}</option>@endforeach</select></div>
                <div id="boxJudul" class="mb-2 d-none"><label class="form-label" for="title">Judul surat (untuk arsip)</label><input name="title" id="title" class="form-control" value="{{ old('title') }}" placeholder="mis. Surat Undangan Rapat" maxlength="150"></div>
                <div class="mb-2"><label class="form-label" for="wg">Warga pemohon</label>
                    <select name="household_id" id="wg" class="form-select"><option value="">- tanpa data warga -</option>
                        @foreach ($households as $h)<option value="{{ $h->id }}" @selected(old('household_id') == $h->id)>{{ $h->head_name }}{{ $h->houseLabel() ? ' - '.$h->houseLabel() : '' }}</option>@endforeach</select></div>
                <div class="mb-2"><label class="form-label" for="nomor">Nomor surat</label><input name="number" id="nomor" class="form-control" value="{{ old('number', $nextNumber) }}" maxlength="80"></div>
                <div class="mb-2"><label class="form-label" for="kep">Keperluan</label><input name="purpose" id="kep" class="form-control" value="{{ old('purpose') }}" placeholder="mis. melamar pekerjaan" maxlength="250"></div>
                <div id="extra"></div>
                <div class="mt-3"><label class="form-label">Tanda tangan Ketua</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="signature" id="ttdQr" value="qr" @checked(old('signature', 'qr') === 'qr')><label class="btn btn-outline-primary btn-sm" for="ttdQr"><i class="bi bi-qr-code me-1"></i>Digital (QR)</label>
                        <input type="radio" class="btn-check" name="signature" id="ttdBasah" value="basah" @checked(old('signature') === 'basah')><label class="btn btn-outline-primary btn-sm" for="ttdBasah"><i class="bi bi-pen me-1"></i>Basah (tanpa QR)</label>
                    </div>
                    <div class="form-text">Ketua masih bisa mengubah pilihan ini saat menandatangani.</div></div>
                <div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="with_letterhead" id="pk" value="1" @checked(old('with_letterhead', '1'))><label class="form-check-label" for="pk">Pakai kop surat</label></div>
            </div>
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-light" id="apply"><i class="bi bi-magic me-1"></i>Isi ulang dari templat</button>
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan draf &amp; lihat</button>
            </div>
            <div class="panel mt-3"><h2 class="h6 fw-bold">Sisipkan variabel</h2>@include('letters.partials.variables')</div>
        </div>
        <div class="col-xl-8"><div class="panel p-0">@include('letters.partials.toolbar')
            <div class="a4-stage"><div class="a4"><div id="kop">@include('letters.partials.letterhead')</div><div id="editor" contenteditable="true" spellcheck="false">{!! old('body', '<p><br></p>') !!}</div><div id="foot">@include('letters.partials.footer')</div></div></div>
        </div></div>
    </div>
</form>
@endsection

@push('scripts')
<script src="{{ asset('js/editor.js') }}"></script>
<script>
(function () {
    const TPL = @json($templates), WG = @json($householdData), BASE = @json($baseData), ADA_LAMA = @json((bool) old('body'));
    const $ = id => document.getElementById(id), ed = $('editor');
    const BLN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const tgl = v => { const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v); return m ? (+m[3]) + ' ' + BLN[+m[2] - 1] + ' ' + m[1] : v; };
    const esc = s => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    const manual = () => $('m2').checked;
    function gambarKolom() {
        const box = $('extra'); box.innerHTML = ''; if (manual()) return;
        ((TPL[$('tpl').value] || {}).fields || []).forEach(x => {
            const d = document.createElement('div'); d.className = 'mb-2';
            const l = document.createElement('label'); l.className = 'form-label'; l.textContent = x.label + (x.required ? ' *' : '');
            const i = document.createElement(x.type === 'textarea' ? 'textarea' : 'input'); i.className = 'form-control';
            i.name = 'fields[' + x.key + ']'; i.dataset.key = x.key; i.dataset.type = x.type;
            if (x.type === 'date' || x.type === 'number') i.type = x.type;
            i.addEventListener('change', isi); d.append(l, i); box.appendChild(d);
        });
    }
    function isi() {
        if (manual()) return;
        const t = TPL[$('tpl').value]; if (!t) return;
        const d = Object.assign({}, BASE, WG[$('wg').value] || {}, { nomor_surat: $('nomor').value, keperluan: $('kep').value });
        document.querySelectorAll('#extra [data-key]').forEach(i => d[i.dataset.key] = i.dataset.type === 'date' ? tgl(i.value) : i.value);
        ed.innerHTML = t.body.replace(/\{\{\s*([a-z0-9_]+)\s*\}\}/gi, (m, k) => { k = k.toLowerCase(); return (k !== 'qr_ttd' && d[k]) ? esc(d[k]) : m; });
    }
    function mode() {
        const m = manual();
        $('boxTpl').classList.toggle('d-none', m); $('boxJudul').classList.toggle('d-none', !m); $('apply').classList.toggle('d-none', m);
        $('title').required = m; gambarKolom(); if (!m) isi();
    }
    document.querySelectorAll('[name=mode]').forEach(r => r.onchange = mode);
    $('tpl').onchange = () => { gambarKolom(); isi(); };
    ['wg', 'nomor', 'kep'].forEach(id => $(id).addEventListener('change', isi));
    $('apply').onclick = isi;
    $('pk').onchange = () => { const v = $('pk').checked ? '' : 'none'; $('kop').style.display = v; $('foot').style.display = v; };
    $('formSurat').addEventListener('submit', () => $('body').value = ed.innerHTML);
    gambarKolom(); $('boxTpl').classList.toggle('d-none', manual()); $('boxJudul').classList.toggle('d-none', !manual()); $('apply').classList.toggle('d-none', manual());
    if (!ADA_LAMA && !manual() && Object.keys(TPL).length) isi();
    $('pk').onchange();
})();
</script>
@endpush
