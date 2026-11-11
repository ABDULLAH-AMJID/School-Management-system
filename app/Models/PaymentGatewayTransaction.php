<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayTransaction extends Model
{
    protected $guarded = [];

    protected $hidden = ['request_payload', 'response_payload'];

    protected function casts(): array
    {
        return ['request_payload' => 'array', 'response_payload' => 'array', 'expires_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }
}
