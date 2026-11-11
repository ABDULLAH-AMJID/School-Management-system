<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetablePeriod extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_break' => 'boolean'];
    }
}
