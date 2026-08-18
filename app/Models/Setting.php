<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['review_threshold', 'updated_by'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'review_threshold' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::create([]);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
