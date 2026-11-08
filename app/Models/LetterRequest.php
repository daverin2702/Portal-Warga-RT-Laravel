<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['household_id', 'user_id', 'letter_template_id', 'template_name', 'purpose', 'field_values', 'status', 'note', 'letter_id', 'log'])]
class LetterRequest extends Model
{
    protected function casts(): array
    {
        return ['field_values' => 'array', 'log' => 'array'];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LetterTemplate::class, 'letter_template_id');
    }

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class);
    }
}
