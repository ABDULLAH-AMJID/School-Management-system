<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankReconciliationItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['transaction_date' => 'date', 'cleared' => 'boolean'];
    }
}
