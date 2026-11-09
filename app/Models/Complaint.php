<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['household_id', 'user_id', 'category', 'title', 'description', 'photo_path', 'status', 'response', 'handled_by'])]
class Complaint extends Model
{
    public const CATEGORIES = ['Lampu jalan', 'Jalan rusak', 'Drainase / banjir', 'Sampah', 'Keamanan', 'Fasilitas umum', 'Usulan kegiatan', 'Lainnya'];

    public const STATUS = ['baru' => 'Baru', 'diproses' => 'Diproses', 'selesai' => 'Selesai'];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
