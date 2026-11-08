<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['date', 'type', 'category', 'allocation', 'description', 'amount', 'status', 'bill_id', 'created_by', 'log'])]
class CashTransaction extends Model
{
    protected function casts(): array
    {
        return ['date' => 'date', 'amount' => 'integer', 'log' => 'array'];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'disetujui');
    }

    /** Saldo kas dari transaksi yang sudah disetujui (opsional sampai sebelum tanggal tertentu). */
    public static function balance(?string $before = null): int
    {
        return (int) static::query()->approved()
            ->when($before, fn ($q) => $q->where('date', '<', $before))
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'masuk' THEN amount ELSE -amount END), 0) as saldo")
            ->value('saldo');
    }

    public function label(): string
    {
        return $this->description ?: $this->category;
    }
}
