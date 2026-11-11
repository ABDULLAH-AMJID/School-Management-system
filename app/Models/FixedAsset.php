<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixedAsset extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['purchased_on' => 'date'];
    }
}
