<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'type', 'message', 'location', 'resolved_at', 'resolved_by'])]
class Emergency extends Model
{
    public const TYPES = ['Kebakaran', 'Pencurian / maling', 'Darurat medis', 'Bencana / banjir', 'Keributan', 'Lainnya'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Sinyal yang masih aktif (belum ditangani, maksimal 6 jam terakhir). */
    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at')->where('created_at', '>', now()->subHours(6));
    }
}
