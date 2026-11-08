<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'position', 'household_id', 'photo', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Pengurus RT atau admin (punya akses ke menu kepengurusan). */
    public function isBoard(): bool
    {
        return in_array($this->role, ['admin', 'pengurus'], true);
    }

    public function holds(string ...$positions): bool
    {
        return in_array($this->position, $positions, true);
    }

    public function roleLabel(): string
    {
        return config('rt.roles.'.$this->role, 'Warga');
    }

    /** Label singkat untuk sidebar: jabatan jika ada, selain itu hak akses. */
    public function displayRole(): string
    {
        return $this->position !== 'warga' ? position_label($this->position) : $this->roleLabel();
    }

    public function photoUrl(): ?string
    {
        return upload_url($this->photo);
    }

    public function initials(): string
    {
        return initials($this->name);
    }

    public function scopeOrderByPosition($query)
    {
        $keys = array_keys(config('rt.positions'));
        $cases = collect($keys)->map(fn ($k, $i) => "WHEN '".$k."' THEN ".$i)->implode(' ');

        return $query->orderByRaw("CASE position $cases ELSE 99 END")->orderBy('name');
    }
}
