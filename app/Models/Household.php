<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'head_name', 'family_card_number', 'head_nik', 'birth_place', 'birth_date', 'gender', 'religion', 'occupation',
    'street', 'block', 'house_number', 'members_count', 'residency_status', 'phone', 'email',
    'emergency_contact_name', 'emergency_contact_phone',
])]
class Household extends Model
{
    public const RESIDENCY = ['owner' => 'Pemilik', 'tenant' => 'Penyewa'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'members_count' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** "Blok A No. 12" */
    public function houseLabel(): string
    {
        return trim(($this->block ? 'Blok '.$this->block : '').($this->house_number ? ' No. '.$this->house_number : ''));
    }

    /** Alamat lengkap untuk surat: "Jl. Mawar, Blok A No. 12" */
    public function fullAddress(): string
    {
        return trim(collect([$this->street, $this->houseLabel()])->filter()->implode(', '));
    }

    public function residencyLabel(): string
    {
        return self::RESIDENCY[$this->residency_status] ?? '-';
    }

    public function scopeOrderByHouse($query)
    {
        return $query->orderBy('block')->orderByRaw('CAST(house_number AS UNSIGNED)')->orderBy('head_name');
    }
}
