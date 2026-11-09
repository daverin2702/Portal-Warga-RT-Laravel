<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Emergency;
use App\Support\Notify;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmergencyController extends Controller
{
    /** Tombol darurat: sinyal ke semua pengurus dan warga. Dibatasi 1 kali per 2 menit per akun. */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'type' => ['required', Rule::in(Emergency::TYPES)],
            'message' => ['nullable', 'string', 'max:300'],
        ]);
        if (Emergency::query()->where('user_id', $user->id)->where('created_at', '>', now()->subMinutes(2))->exists()) {
            return response()->json(['error' => 'Sinyal darurat Anda baru saja terkirim. Tunggu 2 menit sebelum mengirim lagi.'], 429);
        }
        $location = $user->household?->fullAddress();
        $e = Emergency::query()->create(['user_id' => $user->id, 'type' => $data['type'], 'message' => $data['message'] ?? null, 'location' => $location ?: null]);
        Notify::send(Notify::everyone(), 'darurat', 'DARURAT: '.$e->type, $user->name.($location ? ' - '.$location : '').($e->message ? '. '.$e->message : ''), route('dashboard'), includeActor: true);

        return response()->json(['ok' => true, 'message' => 'Sinyal darurat terkirim ke semua pengurus dan warga.']);
    }

    public function resolve(Request $request, Emergency $emergency): JsonResponse
    {
        abort_unless($request->user()->isBoard(), 403);
        if ($emergency->resolved_at) {
            return response()->json(['error' => 'Sinyal ini sudah ditangani.'], 409);
        }
        $emergency->update(['resolved_at' => now(), 'resolved_by' => $request->user()->name]);
        Notify::send(Notify::everyone(), 'info', 'Situasi aman: '.$emergency->type, 'Ditangani oleh '.$request->user()->name.($emergency->location ? ' - '.$emergency->location : ''), route('dashboard'), includeActor: true);

        return response()->json(['ok' => true]);
    }
}
