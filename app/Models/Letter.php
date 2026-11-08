<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'letter_template_id', 'household_id', 'created_by', 'number', 'title', 'body', 'purpose',
    'with_letterhead', 'qr_signature', 'status', 'token', 'signer_name', 'issued_at', 'log',
])]
class Letter extends Model
{
    protected function casts(): array
    {
        return [
            'with_letterhead' => 'boolean',
            'qr_signature' => 'boolean',
            'issued_at' => 'datetime',
            'log' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LetterTemplate::class, 'letter_template_id');
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function request(): HasOne
    {
        return $this->hasOne(LetterRequest::class);
    }

    public function isIssued(): bool
    {
        return $this->status === 'terbit';
    }

    public function verifyUrl(): string
    {
        $base = trim((string) setting('public_url'));

        return $base !== '' ? rtrim($base, '/').'/verifikasi/'.$this->token : route('letters.verify', $this->token);
    }
}
