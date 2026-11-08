<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'amount', 'allocation', 'mandatory', 'active'])]
class BillType extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'integer', 'mandatory' => 'boolean', 'active' => 'boolean'];
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
}
