<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
