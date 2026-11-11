<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeFineApplication extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['applied_on' => 'date'];
    }
}
