<?php

namespace App\Http\Controllers\Letters;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Services\LetterRenderer;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Buat surat (templat atau manual), arsip, dan cetak A4. */
class LetterController extends Controller
{
    public function __construct(private LetterRenderer $renderer) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $letters = Letter::query()->with('household')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('number', 'like', "%$q%")->orWhere('title', 'like', "%$q%")
                ->orWhereHas('household', fn ($h) => $h->where('head_name', 'like', "%$q%"))))
            ->when($request->query('status'), fn ($query, $s) => $query->where('status', $s))
            ->latest()->paginate(25)->withQueryString();

        return view('letters.archive', ['letters' => $letters, 'q' => $q]);
    }

    public function create(): View
    {
        $templates = LetterTemplate::query()->orderBy('name')->get();
        $households = Household::query()->orderByHouse()->get();

        return view('letters.create', [
            'templates' => $templates->mapWithKeys(fn ($t) => [$t->id => ['name' => $t->name, 'body' => $t->body, 'fields' => $t->fieldList()]]),
            'households' => $households,
            'householdData' => $households->mapWithKeys(fn ($h) => [$h->id => $this->renderer->data($h)]),
            'baseData' => $this->renderer->data(null),
            'nextNumber' => $this->renderer->nextNumber(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['templat', 'manual'])],
            'letter_template_id' => ['nullable', 'required_if:mode,templat', 'exists:letter_templates,id'],
            'title' => ['nullable', 'required_if:mode,manual', 'string', 'max:150'],
            'household_id' => ['nullable', 'exists:households,id'],
            'number' => ['nullable', 'string', 'max:80'],
            'purpose' => ['nullable', 'string', 'max:250'],
            'body' => ['required', 'string', 'max:300000'],
            'fields' => ['nullable', 'array'],
            'signature' => ['required', Rule::in(['qr', 'basah'])],
        ]);
        $template = $data['mode'] === 'templat' ? LetterTemplate::query()->find($data['letter_template_id']) : null;
        $household = ! empty($data['household_id']) ? Household::query()->find($data['household_id']) : null;
        $number = trim((string) ($data['number'] ?? '')) ?: $this->renderer->nextNumber();
        $extra = ($template ? $this->renderer->fieldValues($template, $data['fields'] ?? []) : [])
            + ['nomor_surat' => $number, 'keperluan' => trim((string) ($data['purpose'] ?? ''))];
        $body = $this->renderer->sanitize($this->renderer->fill($data['body'], $this->renderer->data($household, $extra)));
        if (trim(strip_tags($body)) === '') {
            return back()->withInput()->with('error', 'Isi surat masih kosong.');
        }

        $user = $request->user();
        $letter = Letter::query()->create([
            'letter_template_id' => $template?->id, 'household_id' => $household?->id, 'created_by' => $user->id,
            'number' => $number, 'title' => $template?->name ?? trim($data['title']), 'body' => $body,
            'purpose' => ($data['purpose'] ?? null) ?: null, 'with_letterhead' => $request->boolean('with_letterhead'),
            'qr_signature' => $data['signature'] === 'qr', 'status' => 'draf', 'token' => Str::random(32),
            'log' => [['at' => now()->format('d/m/Y H:i'), 'by' => $user->name.' ('.$user->displayRole().')', 'action' => 'Draf dibuat', 'note' => '']],
        ]);

        Notify::send(Notify::ability('periksa-surat'), 'persetujuan', 'Draf surat perlu diperiksa', $letter->title.' - '.$letter->number, route('letters.approvals.index'));

        return redirect()->route('letters.print', $letter)->with('success', 'Draf tersimpan. Surat menunggu pemeriksaan Wakil Ketua dan tanda tangan Ketua sebelum berlaku.');
    }

    public function print(Request $request, Letter $letter): View
    {
        $user = $request->user();
        abort_unless($user->isBoard() || ($letter->isIssued() && $letter->household_id && $letter->household_id === $user->household_id), 404);

        return view('letters.print', ['letter' => $letter]);
    }

    public function destroy(Letter $letter): RedirectResponse
    {
        $letter->delete();

        return back()->with('success', 'Surat dihapus dari arsip. QR-nya tidak lagi berlaku.');
    }
}
