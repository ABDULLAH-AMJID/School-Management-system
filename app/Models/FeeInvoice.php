<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeInvoice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'due_date' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getBalanceAttribute(): int
    {
        return $this->total - $this->paid;
    }

    public function items(): HasMany
    {
        return $this->hasMany(FeeInvoiceItem::class);
    }
}
