<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guardian extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['whatsapp_opt_in' => 'boolean'];
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)->withPivot(['primary_contact', 'fee_responsible']);
    }
}
