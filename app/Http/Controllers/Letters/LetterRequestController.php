<?php

namespace App\Http\Controllers\Letters;

use App\Http\Controllers\Controller;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Surat Pengantar: warga mengajukan dan memantau; pengurus melihat semua pengajuan. */
class LetterRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $requests = LetterRequest::query()->with(['household', 'letter'])
            ->when(! $user->isBoard(), fn ($q) => $q->where('household_id', $user->household_id ?? 0))
            ->latest()->paginate(20);

        return view('letters.requests', [
            'requests' => $requests,
            'templates' => LetterTemplate::query()->orderBy('name')->get(),
            'canRequest' => ! $user->isBoard() && $user->household_id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->isBoard() || ! $user->household_id, 403, 'Pengajuan surat hanya untuk akun warga yang terhubung ke data keluarga.');

        $data = $request->validate([
            'letter_template_id' => ['required', 'exists:letter_templates,id'],
            'purpose' => ['required', 'string', 'max:250'],
            'fields' => ['nullable', 'array'],
        ]);
        $template = LetterTemplate::query()->findOrFail($data['letter_template_id']);
        $values = [];
        foreach ($template->fieldList() as $f) {
            $v = trim((string) ($data['fields'][$f['key']] ?? ''));
            if (! empty($f['required']) && $v === '') {
                throw ValidationException::withMessages(['fields.'.$f['key'] => $f['label'].' wajib diisi.']);
            }
            $values[$f['key']] = mb_substr($v, 0, 500);
        }

        LetterRequest::query()->create([
            'household_id' => $user->household_id, 'user_id' => $user->id, 'letter_template_id' => $template->id,
            'template_name' => $template->name, 'purpose' => $data['purpose'], 'field_values' => $values, 'status' => 'diajukan',
            'log' => [['at' => now()->format('d/m/Y H:i'), 'by' => $user->name.' (Warga)', 'action' => 'Diajukan', 'note' => '']],
        ]);

        Notify::send(Notify::ability('teruskan-surat'), 'persetujuan', 'Pengajuan surat baru', $user->name.': '.$template->name, route('letters.approvals.index'));

        return back()->with('success', 'Pengajuan surat terkirim. Prosesnya: diteruskan Sekretaris, diperiksa Wakil Ketua, ditandatangani Ketua.');
    }
}
