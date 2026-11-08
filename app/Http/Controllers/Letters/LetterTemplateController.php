<?php

namespace App\Http\Controllers\Letters;

use App\Http\Controllers\Controller;
use App\Models\LetterTemplate;
use App\Services\LetterRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LetterTemplateController extends Controller
{
    public function __construct(private LetterRenderer $renderer) {}

    public function index(): View
    {
        return view('letters.templates.index', ['templates' => LetterTemplate::query()->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('letters.templates.form', ['template' => new LetterTemplate(['body' => self::starterBody(), 'fields' => []])]);
    }

    public function store(Request $request): RedirectResponse
    {
        LetterTemplate::query()->create($this->validated($request));

        return redirect()->route('letters.templates.index')->with('success', 'Templat dibuat.');
    }

    public function edit(LetterTemplate $template): View
    {
        return view('letters.templates.form', ['template' => $template]);
    }

    public function update(Request $request, LetterTemplate $template): RedirectResponse
    {
        $template->update($this->validated($request, $template));

        return redirect()->route('letters.templates.index')->with('success', 'Templat diperbarui.');
    }

    public function duplicate(LetterTemplate $template): RedirectResponse
    {
        $name = Str::limit($template->name, 85, '').' (salinan)';
        $i = 1;
        while (LetterTemplate::query()->where('name', $name)->exists()) {
            $name = Str::limit($template->name, 80, '').' (salinan '.(++$i).')';
        }
        LetterTemplate::query()->create(['name' => $name, 'body' => $template->body, 'fields' => $template->fields]);

        return back()->with('success', 'Templat diduplikasi sebagai "'.$name.'".');
    }

    public function destroy(LetterTemplate $template): RedirectResponse
    {
        $template->delete();

        return back()->with('success', 'Templat dihapus. Surat yang sudah terbit tetap aman di arsip.');
    }

    private function validated(Request $request, ?LetterTemplate $template = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('letter_templates')->ignore($template?->id)],
            'body' => ['required', 'string', 'max:200000'],
            'fields' => ['nullable', 'array', 'max:20'],
            'fields.*.label' => ['nullable', 'string', 'max:80'],
            'fields.*.type' => ['nullable', Rule::in(array_keys(LetterTemplate::FIELD_TYPES))],
            'fields.*.required' => ['nullable', 'boolean'],
        ]);
        $fields = [];
        $seen = [];
        foreach ($data['fields'] ?? [] as $f) {
            $label = trim((string) ($f['label'] ?? ''));
            $key = Str::of($label)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->limit(40, '')->toString();
            if ($label === '' || $key === '' || in_array($key, LetterTemplate::RESERVED, true) || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $fields[] = ['key' => $key, 'label' => $label, 'type' => $f['type'] ?? 'text', 'required' => ! empty($f['required'])];
        }
        $body = $this->renderer->sanitize($data['body']);
        if (trim(strip_tags($body)) === '') {
            throw ValidationException::withMessages(['body' => 'Isi templat masih kosong.']);
        }

        return ['name' => trim($data['name']), 'body' => $body, 'fields' => $fields];
    }

    public static function starterBody(): string
    {
        return '<h3 style="text-align:center;margin:0"><u>JUDUL SURAT</u></h3><p style="text-align:center;margin:0 0 12px">Nomor: {{nomor_surat}}</p>'
            .'<p>Yang bertanda tangan di bawah ini, Ketua RT {{rt}} RW {{rw}}, Kelurahan {{kelurahan}}, Kecamatan {{kecamatan}}, {{kota}}, menerangkan bahwa <b>{{nama}}</b>, beralamat di {{alamat}}, ...</p>'
            .'<p>Surat ini dibuat untuk keperluan: <b>{{keperluan}}</b>.</p><p>Demikian surat ini dibuat untuk dipergunakan sebagaimana mestinya.</p>'
            .'<div style="width:48%;margin-left:52%;text-align:center"><p style="margin:12px 0 0">{{kota}}, {{tanggal_surat}}<br>Ketua RT {{rt}} / RW {{rw}}</p><p style="margin:6px 0">{{qr_ttd}}</p><p style="margin:0"><b><u>{{nama_ketua}}</u></b></p></div>';
    }
}
