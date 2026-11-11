<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['fitness_expires_on' => 'date'];
    }
}
