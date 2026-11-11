<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeGenerationRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['generated_at' => 'datetime'];
    }
}
