<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryBook extends Model
{
    protected $guarded = [];

    public function copies(): HasMany
    {
        return $this->hasMany(LibraryCopy::class);
    }
}
