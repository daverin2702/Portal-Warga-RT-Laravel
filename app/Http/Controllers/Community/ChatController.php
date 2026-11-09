<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Chat pop-up: Grup Warga, Grup Pengurus, dan pesan pribadi. Warga hanya bisa mengirim pesan pribadi ke pengurus. */
class ChatController extends Controller
{
    public static function rooms(User $user): array
    {
        $rooms = ['umum'];
        if ($user->isBoard()) {
            $rooms[] = 'pengurus';
        }
        $dm = ChatMessage::query()->where(fn ($q) => $q->where('room', 'like', 'dm-'.$user->id.'-%')->orWhere('room', 'like', 'dm-%-'.$user->id))
            ->distinct()->pluck('room')->filter(fn ($r) => ChatMessage::canAccess($user, $r));

        return array_values(array_unique(array_merge($rooms, $dm->all())));
    }

    public static function unread(User $user, string $room): int
    {
        $last = (int) DB::table('chat_reads')->where('user_id', $user->id)->where('room', $room)->value('last_id');

        return ChatMessage::query()->where('room', $room)->where('id', '>', $last)->where('user_id', '<>', $user->id)->count();
    }

    public static function unreadTotal(User $user): int
    {
        return array_sum(array_map(fn ($r) => static::unread($user, $r), static::rooms($user)));
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $list = collect(static::rooms($user))->map(function ($room) use ($user) {
            if ($room === 'umum' || $room === 'pengurus') {
                $name = $room === 'umum' ? 'Grup Warga' : 'Grup Pengurus';
            } else {
                preg_match('/^dm-(\d+)-(\d+)$/', $room, $m);
                $other = (int) $m[1] === $user->id ? (int) $m[2] : (int) $m[1];
                $name = User::query()->whereKey($other)->value('name') ?? 'Pengguna';
            }
            $last = ChatMessage::query()->where('room', $room)->latest('id')->first();

            return ['room' => $room, 'name' => $name, 'group' => ! str_starts_with($room, 'dm-'), 'unread' => static::unread($user, $room),
                'last' => $last ? mb_substr($last->body, 0, 60) : '', 'at' => $last?->created_at?->toIso8601String() ?? ''];
        })->sortBy([['group', 'desc'], ['at', 'desc']])->values();

        $contacts = User::query()->whereKeyNot($user->id)
            ->when(! $user->isBoard(), fn ($q) => $q->whereIn('role', ['pengurus', 'admin']))
            ->orderByRaw("CASE role WHEN 'pengurus' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")->orderBy('name')
            ->get(['id', 'name', 'role', 'position'])
            ->map(fn ($u) => ['room' => ChatMessage::dmRoom($user->id, $u->id), 'name' => $u->name, 'label' => $u->displayRole()]);

        return response()->json(['rooms' => $list, 'contacts' => $contacts]);
    }

    public function show(Request $request, string $room): JsonResponse
    {
        $user = $request->user();
        abort_unless(ChatMessage::canAccess($user, $room), 403);
        $after = (int) $request->query('setelah', 0);
        $messages = ChatMessage::query()->with('user:id,name')->where('room', $room)->where('id', '>', $after)
            ->latest('id')->limit(100)->get()->reverse()->values();
        if ($messages->isNotEmpty()) {
            DB::table('chat_reads')->upsert([['user_id' => $user->id, 'room' => $room, 'last_id' => $messages->last()->id]], ['user_id', 'room'], ['last_id']);
        }

        return response()->json(['messages' => $messages->map(fn ($m) => [
            'id' => $m->id, 'mine' => $m->user_id === $user->id, 'name' => $m->user?->name, 'body' => $m->body, 'at' => $m->created_at->toIso8601String(),
        ])]);
    }

    public function store(Request $request, string $room): JsonResponse
    {
        $user = $request->user();
        abort_unless(ChatMessage::canAccess($user, $room), 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);
        if (str_starts_with($room, 'dm-') && ! $user->isBoard()) {
            preg_match('/^dm-(\d+)-(\d+)$/', $room, $m);
            $other = (int) $m[1] === $user->id ? (int) $m[2] : (int) $m[1];
            if (! User::query()->whereKey($other)->whereIn('role', ['pengurus', 'admin'])->exists()) {
                return response()->json(['error' => 'Warga hanya dapat mengirim pesan pribadi ke pengurus.'], 403);
            }
        }
        ChatMessage::query()->create(['room' => $room, 'user_id' => $user->id, 'body' => trim($data['body'])]);

        return response()->json(['ok' => true]);
    }
}
