<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetablePublication extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }
}
