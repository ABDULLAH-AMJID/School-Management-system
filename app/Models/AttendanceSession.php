<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['attendance_date' => 'date', 'submitted_at' => 'datetime'];
    }

    public function records(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
