<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'household_id', 'bill_type_id', 'name', 'period', 'allocation', 'amount', 'status', 'payment_method',
    'proof_path', 'proof_uploaded_at', 'rejection_note', 'receipt_number', 'paid_at', 'verified_by',
])]
class Bill extends Model
{
    public const STATUS = ['belum' => 'Belum dibayar', 'menunggu' => 'Menunggu verifikasi', 'lunas' => 'Lunas'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'proof_uploaded_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(BillType::class, 'bill_type_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** "November 2026" */
    public function periodLabel(): string
    {
        return Carbon::createFromFormat('Y-m-d', $this->period.'-01')->locale('id')->translatedFormat('F Y');
    }
}
