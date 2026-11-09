<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['room', 'user_id', 'body'])]
class ChatMessage extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function dmRoom(int $a, int $b): string
    {
        return 'dm-'.min($a, $b).'-'.max($a, $b);
    }

    /** Apakah pengguna boleh membaca/menulis di ruang ini. */
    public static function canAccess(User $user, string $room): bool
    {
        if ($room === 'umum') {
            return true;
        }
        if ($room === 'pengurus') {
            return $user->isBoard();
        }
        if (preg_match('/^dm-(\d+)-(\d+)$/', $room, $m)) {
            return (int) $m[1] < (int) $m[2] && in_array($user->id, [(int) $m[1], (int) $m[2]], true);
        }

        return false;
    }
}
