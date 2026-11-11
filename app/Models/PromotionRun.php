<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromotionRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(PromotionDecision::class);
    }

    public function fromSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'from_section_id');
    }

    public function toSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'to_section_id');
    }
}
