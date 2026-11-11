<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;

        return view('inventory.index', ['items' => InventoryItem::where('school_id', $schoolId)->orderBy('name')->get(), 'movements' => StockMovement::where('school_id', $schoolId)->with('item')->latest('occurred_at')->limit(25)->get(), 'assets' => FixedAsset::where('school_id', $schoolId)->get()]);
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $data = $request->validate(['sku' => ['required', 'string', 'max:40', Rule::unique('inventory_items')->where('school_id', $request->user()->school_id)], 'name' => ['required', 'string', 'max:150'], 'category' => ['required', 'string', 'max:80'], 'unit' => ['required', 'string', 'max:30'], 'reorder_level' => ['required', 'numeric', 'min:0'], 'location' => ['nullable', 'string', 'max:100']]);
        InventoryItem::create($data + ['school_id' => $request->user()->school_id]);

        return back()->with('success', 'Inventory item created.');
    }

    public function moveStock(Request $request, InventoryItem $item): RedirectResponse
    {
        abort_unless($item->school_id === $request->user()->school_id, 403);
        $data = $request->validate(['type' => ['required', Rule::in(['receipt', 'issue', 'return', 'adjustment_in', 'adjustment_out'])], 'quantity' => ['required', 'numeric', 'gt:0'], 'unit_cost' => ['nullable', 'numeric', 'min:0'], 'reference' => ['nullable', 'string', 'max:100'], 'issued_to' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:500']]);
        DB::transaction(function () use ($item, $data, $request) {
            $item = InventoryItem::lockForUpdate()->findOrFail($item->id);
            $incoming = in_array($data['type'], ['receipt', 'return', 'adjustment_in'], true);
            $delta = $incoming ? (float) $data['quantity'] : -((float) $data['quantity']);
            if (((float) $item->quantity + $delta) < 0) {
                throw ValidationException::withMessages(['quantity' => 'Insufficient stock for this transaction.']);
            }StockMovement::create(['school_id' => $item->school_id, 'inventory_item_id' => $item->id, 'type' => $data['type'], 'quantity' => $data['quantity'], 'unit_cost' => (int) round(($data['unit_cost'] ?? 0) * 100), 'reference' => $data['reference'] ?? null, 'issued_to' => $data['issued_to'] ?? null, 'notes' => $data['notes'] ?? null, 'created_by' => $request->user()->id, 'occurred_at' => now()]);
            $item->increment('quantity', $delta);
            if ($incoming && isset($data['unit_cost'])) {
                $item->update(['average_cost' => (int) round($data['unit_cost'] * 100)]);
            }
        });

        return back()->with('success', 'Stock movement posted.');
    }

    public function storeAsset(Request $request): RedirectResponse
    {
        $data = $request->validate(['asset_tag' => ['required', 'string', 'max:50', Rule::unique('fixed_assets')->where('school_id', $request->user()->school_id)], 'name' => ['required', 'string', 'max:150'], 'category' => ['required', 'string', 'max:80'], 'purchase_cost' => ['nullable', 'numeric', 'min:0'], 'location' => ['nullable', 'string', 'max:100']]);
        $data['purchase_cost'] = (int) round(($data['purchase_cost'] ?? 0) * 100);
        FixedAsset::create($data + ['school_id' => $request->user()->school_id]);

        return back()->with('success','Fixed asset registered.');
    }
}
