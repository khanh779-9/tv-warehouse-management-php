<?php
namespace App\Http\Controllers;
use App\Models\PurchaseOrder;
use App\Services\{AuditService, InventoryService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PurchaseOrderController extends Controller
{
  public function index(Request $r)
  {
    return PurchaseOrder::with(['supplier:id,code,name', 'warehouse:id,code,name', 'approver:id,name'])->withCount('items')->latest()->paginate(20);
  }
  public function store(Request $r, AuditService $audit)
  {
    $d = $r->validate(['supplier_id' => 'required|exists:suppliers,id', 'warehouse_id' => 'required|exists:warehouses,id', 'ordered_at' => 'required|date', 'expected_at' => 'nullable|date', 'notes' => 'nullable|string', 'items' => 'required|array|min:1', 'items.*.product_id' => 'required|exists:products,id', 'items.*.quantity' => 'required|numeric|gt:0', 'items.*.unit_cost' => 'required|numeric|min:0']);
    return DB::transaction(function () use ($d, $r, $audit) {
      $po = PurchaseOrder::create(['po_number' => 'PO-' . now()->format('Ymd-His') . '-' . random_int(100, 999), 'supplier_id' => $d['supplier_id'], 'warehouse_id' => $d['warehouse_id'], 'status' => 'DRAFT', 'approval_status' => 'PENDING', 'ordered_at' => $d['ordered_at'], 'expected_at' => $d['expected_at'] ?? null, 'notes' => $d['notes'] ?? null, 'created_by' => $r->user()->id]);
      foreach ($d['items'] as $i)
        $po->items()->create($i + ['received_quantity' => 0]);
      $audit->log($r, 'PO_CREATED', $po);
      return response()->json($po->load(['items.product', 'supplier', 'warehouse']), 201);
    });
  }
  public function show(PurchaseOrder $purchaseOrder)
  {
    return $purchaseOrder->load(['items.product', 'items.serials', 'supplier', 'warehouse', 'approver:id,name']);
  }
  public function approve(Request $r, PurchaseOrder $purchaseOrder, AuditService $audit)
  {
    abort_unless($purchaseOrder->approval_status === 'PENDING', 422, 'Only pending purchase orders can be approved.');
    $before = $purchaseOrder->toArray();
    $purchaseOrder->update(['approval_status' => 'APPROVED', 'status' => 'ORDERED', 'approved_by' => $r->user()->id, 'approved_at' => now()]);
    $audit->log($r, 'PO_APPROVED', $purchaseOrder, $before);
    return $purchaseOrder->fresh(['supplier', 'warehouse', 'approver']);
  }
  public function receive(Request $r, PurchaseOrder $purchaseOrder, InventoryService $inventory, AuditService $audit)
  {
    $d = $r->validate(['items' => 'required|array|min:1', 'items.*.item_id' => 'required|integer', 'items.*.quantity' => 'required|numeric|gt:0', 'items.*.serials' => 'sometimes|array', 'items.*.serials.*.serial_number' => 'required_with:items.*.serials|string|max:120|unique:product_serials,serial_number', 'items.*.serials.*.warehouse_location_id' => 'nullable|exists:warehouse_locations,id']);
    return DB::transaction(function () use ($d, $r, $purchaseOrder, $inventory, $audit) {
      abort_unless($purchaseOrder->approval_status === 'APPROVED', 422, 'Purchase order must be approved before receiving.');
      abort_if(in_array($purchaseOrder->status, ['CANCELLED', 'RECEIVED']), 422, 'Order cannot be received.');
      $purchaseOrder->load('items.product');
      foreach ($d['items'] as $x) {
        $item = $purchaseOrder->items->firstWhere('id', $x['item_id']);
        abort_unless($item, 422, 'Invalid purchase order item.');
        $remaining = (float) $item->quantity - (float) $item->received_quantity;
        abort_if($x['quantity'] > $remaining + 0.0001, 422, 'Received quantity exceeds remaining quantity.');
        if ($item->product->is_serialized) {
          abort_if(abs((float) $x['quantity'] - round((float) $x['quantity'])) > 0.0001, 422, 'TVs must be received in whole units.');
          abort_if(count($x['serials'] ?? []) !== (int) round($x['quantity']), 422, 'Provide exactly one TV serial number for every received unit.');
        }
        $inventory->receive($purchaseOrder->warehouse_id, $item->product_id, (float) $x['quantity'], (float) $item->unit_cost, 'PURCHASE_ORDER', $purchaseOrder->id, $r->user()->id, 'Goods receipt');
        $inventory->receivePurchaseSerials($purchaseOrder->warehouse_id, $item->product_id, $item->id, $x['serials'] ?? [], $r->user()->id);
        $item->increment('received_quantity', $x['quantity']);
      }
      $purchaseOrder->refresh()->load('items');
      $all = $purchaseOrder->items->every(fn($i) => (float) $i->received_quantity >= (float) $i->quantity);
      $purchaseOrder->update(['status' => $all ? 'RECEIVED' : 'PARTIAL']);
      $audit->log($r, 'PO_RECEIVED', $purchaseOrder);
      return $purchaseOrder->fresh(['items.product', 'items.serials', 'supplier', 'warehouse']);
    });
  }
}
