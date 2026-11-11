<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceDevice extends Model
{
    protected $guarded = [];

    protected $hidden = ['api_key_hash'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime'];
    }
}
