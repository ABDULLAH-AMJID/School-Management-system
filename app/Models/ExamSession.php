<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'published_at' => 'datetime'];
    }

    public function components(): HasMany
    {
        return $this->hasMany(AssessmentComponent::class);
    }
}
