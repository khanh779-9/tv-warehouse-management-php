<?php
namespace App\Http\Controllers;
use App\Models\SalesOrder;
use App\Services\{AuditService, InventoryService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class SalesOrderController extends Controller
{
    public function index()
    {
        return SalesOrder::with(['customer:id,code,name', 'warehouse:id,code,name'])->withCount('items')->latest()->paginate(20);
    }
    public function store(Request $r, InventoryService $inventory, AuditService $audit)
    {
        $d = $r->validate(['customer_id' => 'nullable|exists:customers,id', 'warehouse_id' => 'required|exists:warehouses,id', 'ordered_at' => 'required|date', 'channel' => ['required', Rule::in(['DEALER', 'RETAIL_STORE', 'ECOMMERCE', 'INTERNAL'])], 'external_reference' => 'nullable|string|max:100', 'notes' => 'nullable|string', 'items' => 'required|array|min:1', 'items.*.product_id' => 'required|exists:products,id', 'items.*.quantity' => 'required|numeric|gt:0', 'items.*.unit_price' => 'required|numeric|min:0']);
        return DB::transaction(function () use ($d, $r, $inventory, $audit) {
            $so = SalesOrder::create(['so_number' => 'SO-' . now()->format('Ymd-His') . '-' . random_int(100, 999), 'customer_id' => $d['customer_id'] ?? null, 'warehouse_id' => $d['warehouse_id'], 'status' => 'CONFIRMED', 'channel' => $d['channel'], 'external_reference' => $d['external_reference'] ?? null, 'ordered_at' => $d['ordered_at'], 'notes' => $d['notes'] ?? null, 'created_by' => $r->user()->id]);
            foreach ($d['items'] as $i) {
                $item = $so->items()->create($i + ['issued_quantity' => 0]);
                $inventory->reserveSalesOrderItem($item, $r->user()->id);
            }$so->update(['reserved_at' => now()]);
            $audit->log($r, 'SO_CREATED_AND_RESERVED', $so);
            return response()->json($so->load(['items.product', 'reservations', 'customer', 'warehouse']), 201);
        });
    }
    public function show(SalesOrder $salesOrder)
    {
        return $salesOrder->load(['items.product', 'items.serials', 'reservations.product', 'customer', 'warehouse']);
    }
    public function issue(Request $r, SalesOrder $salesOrder, InventoryService $inventory, AuditService $audit)
    {
        $d = $r->validate(['items' => 'required|array|min:1', 'items.*.item_id' => 'required|integer', 'items.*.quantity' => 'required|numeric|gt:0', 'items.*.serial_ids' => 'sometimes|array', 'items.*.serial_ids.*' => 'integer|exists:product_serials,id']);
        return DB::transaction(function () use ($d, $r, $salesOrder, $inventory, $audit) {
            abort_if(in_array($salesOrder->status, ['CANCELLED', 'COMPLETED']), 422, 'Order cannot be issued.');
            $salesOrder->load('items');
            foreach ($d['items'] as $x) {
                $item = $salesOrder->items->firstWhere('id', $x['item_id']);
                abort_unless($item, 422, 'Invalid sales order item.');
                $remaining = (float) $item->quantity - (float) $item->issued_quantity;
                abort_if($x['quantity'] > $remaining + 0.0001, 422, 'Issued quantity exceeds remaining quantity.');
                $inventory->issueSalesOrderItem($item->id, (float) $x['quantity'], $x['serial_ids'] ?? [], $r->user()->id);
                $item->increment('issued_quantity', $x['quantity']);
            }$salesOrder->refresh()->load('items');
            $all = $salesOrder->items->every(fn($i) => (float) $i->issued_quantity >= (float) $i->quantity);
            $salesOrder->update(['status' => $all ? 'COMPLETED' : 'PARTIAL']);
            $audit->log($r, 'SO_FULFILLED', $salesOrder);
            return $salesOrder->fresh(['items.product', 'items.serials', 'reservations', 'customer', 'warehouse']);
        });
    }
    public function cancel(Request $r, SalesOrder $salesOrder, InventoryService $inventory, AuditService $audit)
    {
        return DB::transaction(function () use ($r, $salesOrder, $inventory, $audit) {
            abort_if(in_array($salesOrder->status, ['COMPLETED', 'CANCELLED']), 422, 'Order cannot be cancelled.');
            $before = $salesOrder->toArray();
            $inventory->releaseSalesOrderReservations($salesOrder->id);
            $salesOrder->update(['status' => 'CANCELLED']);
            $audit->log($r, 'SO_CANCELLED', $salesOrder, $before);
            return $salesOrder->fresh(['reservations']);
        });
    }
}
