<?php

namespace App\Support;

use App\Models\User;
use Throwable;

/** Profil Ketua & Wakil untuk halaman depan: dari akun pemegang jabatan atau isian manual. */
class Board
{
    private const MAP = ['chair' => 'ketua', 'vice' => 'wakil'];

    public static function profile(string $who): array
    {
        $profile = [
            'title' => $who === 'chair' ? 'Ketua RT' : 'Wakil Ketua RT',
            'name' => setting($who.'_name'),
            'term' => setting($who.'_term'),
            'message' => setting($who.'_message'),
            'photo' => upload_url(setting($who.'_photo')),
        ];

        if (setting('profile_source') === 'account') {
            try {
                $user = User::query()->where('position', self::MAP[$who])->first();
            } catch (Throwable) {
                $user = null;
            }
            if ($user) {
                $profile['name'] = $user->name;
                $profile['photo'] = $user->photoUrl() ?: $profile['photo'];
            }
        }

        return $profile;
    }

    /** Nama Ketua RT saat ini (untuk tanda tangan surat di tahap berikutnya). */
    public static function chairName(): string
    {
        return static::profile('chair')['name'];
    }
}
