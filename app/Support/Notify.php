<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\PortalNotice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

/** Kirim notifikasi ke kelompok pengguna sesuai peran. Kegagalan notifikasi tidak menggagalkan proses utama. */
class Notify
{
    /** Pemegang wewenang (Gate) tertentu. Jika belum ada pemegangnya, jatuh ke admin. */
    public static function ability(string $ability): Collection
    {
        $positions = config('rt.capabilities.'.$ability, []);
        $users = $positions ? User::query()->where('role', 'pengurus')->whereIn('position', $positions)->get() : collect();

        return $users->isNotEmpty() ? $users : User::query()->where('role', 'admin')->get();
    }

    public static function household(?int $householdId): Collection
    {
        return $householdId ? User::query()->where('household_id', $householdId)->get() : collect();
    }

    public static function everyone(): Collection
    {
        return User::query()->get();
    }

    /**
     * @param  iterable<User>|User|null  $users
     */
    public static function send($users, string $kind, string $title, string $body = '', ?string $url = null, bool $includeActor = false): void
    {
        try {
            $actor = auth()->id();
            $targets = collect($users instanceof User ? [$users] : ($users ?? []))
                ->filter()->unique('id')
                ->reject(fn (User $u) => ! $includeActor && $u->id === $actor);
            if ($targets->isNotEmpty()) {
                Notification::send($targets, new PortalNotice($kind, $title, $body, $url));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
