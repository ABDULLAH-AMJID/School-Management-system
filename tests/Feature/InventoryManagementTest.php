<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_increases_and_issue_decreases_stock(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $item = InventoryItem::firstOrFail();
        $initial = (float) $item->quantity;

        $this->actingAs($principal)->post(route('inventory.movements.store', $item), ['type' => 'receipt', 'quantity' => 10, 'unit_cost' => 1500]);
        $this->assertSame($initial + 10, (float) $item->fresh()->quantity);
        $this->actingAs($principal)->post(route('inventory.movements.store', $item), ['type' => 'issue', 'quantity' => 5, 'issued_to' => 'Science Department']);
        $this->assertSame($initial + 5, (float) $item->fresh()->quantity);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_stock_cannot_become_negative(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $item = InventoryItem::firstOrFail();

        $this->actingAs($principal)->post(route('inventory.movements.store', $item), [
            'type' => 'issue', 'quantity' => (float) $item->quantity + 1,
        ])->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame(48.0, (float) $item->fresh()->quantity);
    }
}
