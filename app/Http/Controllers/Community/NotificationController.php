<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Emergency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /** Halaman semua notifikasi. */
    public function index(Request $request): View
    {
        $user = $request->user();
        $list = $user->notifications()->paginate(30);
        $user->unreadNotifications()->update(['read_at' => now()]);

        return view('community.notifications', ['notifications' => $list]);
    }

    /** Polling ringan dari app.js: lonceng, sinyal darurat aktif, jumlah chat belum dibaca. */
    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->limit(15)->get()->map(fn ($n) => [
                'id' => $n->id, 'kind' => $n->data['kind'] ?? 'info', 'title' => $n->data['title'] ?? '', 'body' => $n->data['body'] ?? '',
                'url' => $n->data['url'] ?? null, 'read' => $n->read_at !== null, 'at' => $n->created_at->toIso8601String(),
            ]),
            'emergencies' => Emergency::query()->active()->with('user:id,name')->latest()->get()->map(fn ($e) => [
                'id' => $e->id, 'type' => $e->type, 'message' => $e->message, 'location' => $e->location,
                'name' => $e->user?->name, 'at' => $e->created_at->toIso8601String(),
            ]),
            'chat' => ChatController::unreadTotal($user),
        ]);
    }

    public function read(Request $request): JsonResponse
    {
        $data = $request->validate(['id' => ['nullable', 'string', 'max:40'], 'all' => ['nullable', 'boolean']]);
        $q = $request->user()->unreadNotifications();
        if (empty($data['all'])) {
            $q->where('id', $data['id'] ?? '');
        }
        $q->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
