<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplaintController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->query('status');

        return view('community.complaints', [
            'complaints' => Complaint::query()->with(['household', 'handler'])
                ->when(! $user->isBoard(), fn ($q) => $q->where('household_id', $user->household_id ?? 0))
                ->when($status && array_key_exists($status, Complaint::STATUS), fn ($q) => $q->where('status', $status))
                ->orderByRaw("CASE status WHEN 'baru' THEN 0 WHEN 'diproses' THEN 1 ELSE 2 END")->latest()
                ->paginate(20)->withQueryString(),
            'canSubmit' => ! $user->isBoard() && $user->household_id,
            'status' => $status,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->isBoard() || ! $user->household_id, 403, 'Aduan dikirim oleh akun warga yang terhubung ke data keluarga.');
        $data = $request->validate([
            'category' => ['required', Rule::in(Complaint::CATEGORIES)],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:3000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
        $c = Complaint::query()->create([
            'household_id' => $user->household_id, 'user_id' => $user->id, 'category' => $data['category'],
            'title' => $data['title'], 'description' => $data['description'],
            'photo_path' => $request->file('photo')?->store('foto-aduan', 'local'),
        ]);
        Notify::send(Notify::ability('tangani-aduan'), 'aduan', 'Aduan baru: '.$c->category, $user->name.': '.$c->title, route('complaints.index'));

        return back()->with('success', 'Aduan terkirim. Pantau statusnya di halaman ini.');
    }

    public function update(Request $request, Complaint $complaint): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Complaint::STATUS))],
            'response' => ['nullable', 'string', 'max:300'],
        ]);
        $complaint->update($data + ['handled_by' => $request->user()->id]);
        Notify::send(Notify::household($complaint->household_id), 'aduan', 'Aduan Anda '.strtolower(Complaint::STATUS[$complaint->status]),
            $complaint->title.($complaint->response ? ': '.$complaint->response : ''), route('complaints.index'));

        return back()->with('success', 'Aduan diperbarui.');
    }

    public function photo(Request $request, Complaint $complaint): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->isBoard() || $complaint->household_id === $user->household_id, 404);
        abort_unless($complaint->photo_path && Storage::disk('local')->exists($complaint->photo_path), 404);

        return Storage::disk('local')->response($complaint->photo_path, null, ['X-Content-Type-Options' => 'nosniff']);
    }
}
