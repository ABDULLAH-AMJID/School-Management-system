<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $fillable = ['school_id', 'public_id', 'admission_no', 'first_name', 'last_name', 'date_of_birth', 'gender', 'status', 'admitted_on', 'photo_path'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'admitted_on' => 'date'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class)->withPivot(['primary_contact', 'fee_responsible']);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
