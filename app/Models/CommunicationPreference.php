<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationPreference extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['opted_out' => 'boolean', 'consented_at' => 'datetime', 'opted_out_at' => 'datetime'];
    }
}
