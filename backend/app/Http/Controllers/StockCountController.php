<?php
namespace App\Http\Controllers;
use App\Models\{StockCount, Stock};
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class StockCountController extends Controller
{
    public function index()
    {
        return StockCount::with('warehouse:id,code,name')->withCount('items')->latest()->paginate(20);
    }
    public function store(Request $r)
    {
        $d = $r->validate(['warehouse_id' => 'required|exists:warehouses,id', 'notes' => 'nullable|string']);
        return DB::transaction(function () use ($d, $r) {
            $c = StockCount::create(['count_number' => 'SC-' . now()->format('Ymd-His') . '-' . random_int(100, 999), 'warehouse_id' => $d['warehouse_id'], 'status' => 'DRAFT', 'notes' => $d['notes'] ?? null, 'created_by' => $r->user()->id]);
            $stocks = Stock::where('warehouse_id', $d['warehouse_id'])->get();
            foreach ($stocks as $s)
                $c->items()->create(['product_id' => $s->product_id, 'system_quantity' => $s->quantity, 'counted_quantity' => $s->quantity, 'difference' => 0]);
            return response()->json($c->load(['items.product', 'warehouse']), 201);
        });
    }
    public function show(StockCount $stockCount)
    {
        return $stockCount->load(['items.product', 'warehouse']);
    }
    public function updateItems(Request $r, StockCount $stockCount)
    {
        abort_unless($stockCount->status === 'DRAFT', 422, 'Count is finalized.');
        $d = $r->validate(['items' => 'required|array', 'items.*.item_id' => 'required|integer', 'items.*.counted_quantity' => 'required|numeric|min:0']);
        foreach ($d['items'] as $x) {
            $i = $stockCount->items()->findOrFail($x['item_id']);
            $i->counted_quantity = $x['counted_quantity'];
            $i->difference = (float) $x['counted_quantity'] - (float) $i->system_quantity;
            $i->save();
        }
        return $stockCount->fresh(['items.product', 'warehouse']);
    }
    public function finalize(Request $r, StockCount $stockCount, InventoryService $inventory)
    {
        return DB::transaction(function () use ($r, $stockCount, $inventory) {
            abort_unless($stockCount->status === 'DRAFT', 422, 'Count already finalized.');
            $stockCount->load('items');
            foreach ($stockCount->items as $i) {
                $diff = $inventory->adjust($stockCount->warehouse_id, $i->product_id, (float) $i->counted_quantity, 'STOCK_COUNT', $stockCount->id, $r->user()->id);
                $i->update(['difference' => $diff]);
            }$stockCount->update(['status' => 'FINALIZED', 'finalized_at' => now()]);
            return $stockCount->fresh(['items.product', 'warehouse']);
        });
    }
}
