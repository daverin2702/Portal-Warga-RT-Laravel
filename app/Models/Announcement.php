<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'body', 'category', 'created_by'])]
class Announcement extends Model
{
    public const CATEGORIES = ['kegiatan' => 'Kegiatan', 'kerja bakti' => 'Kerja bakti', 'siskamling' => 'Siskamling', 'keamanan' => 'Keamanan', 'lainnya' => 'Lainnya'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
