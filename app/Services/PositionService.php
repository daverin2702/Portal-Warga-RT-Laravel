<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Pergantian jabatan pengurus.
 * - Jabatan tunggal (ketua, wakil, sekretaris, wakil sekretaris, bendahara) otomatis dilepas dari pemegang lama.
 * - Pemegang jabatan inti otomatis mendapat akses pengurus; yang dilepas kembali menjadi warga
 *   (selama akunnya terhubung ke data keluarga).
 * - Admin tetap admin apa pun jabatannya.
 */
class PositionService
{
    public function assign(User $user, string $position): void
    {
        if (! array_key_exists($position, config('rt.positions'))) {
            throw new InvalidArgumentException("Jabatan tidak dikenal: $position");
        }

        DB::transaction(function () use ($user, $position) {
            if (in_array($position, config('rt.single_positions'), true)) {
                User::query()->where('position', $position)->whereKeyNot($user->getKey())->get()
                    ->each(fn (User $old) => $this->release($old));
            }

            $user->position = $position;
            if ($user->role !== 'admin') {
                if (in_array($position, config('rt.core_positions'), true)) {
                    $user->role = 'pengurus';
                } elseif ($user->role === 'pengurus' && $user->household_id) {
                    $user->role = 'warga';
                }
            }
            $user->save();
        });
    }

    private function release(User $user): void
    {
        $user->position = 'warga';
        if ($user->role === 'pengurus' && $user->household_id) {
            $user->role = 'warga';
        }
        $user->save();
    }
}
