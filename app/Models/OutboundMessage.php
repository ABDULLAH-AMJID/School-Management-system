<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundMessage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['scheduled_for' => 'datetime', 'sent_at' => 'datetime', 'approved_at' => 'datetime', 'metadata' => 'array'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class);
    }
}
