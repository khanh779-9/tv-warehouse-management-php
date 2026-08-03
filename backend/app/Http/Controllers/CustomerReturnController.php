<?php
namespace App\Http\Controllers;
use App\Models\{CustomerReturn, DeviceEvent, ProductSerial};
use App\Services\{AuditService, InventoryService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class CustomerReturnController extends Controller
{
    public function index()
    {
        return CustomerReturn::with(['customer:id,code,name', 'warehouse:id,code,name'])->withCount('items')->latest()->paginate(30);
    }
    public function show(CustomerReturn $customerReturn)
    {
        return $customerReturn->load(['customer', 'salesOrder', 'warehouse', 'items.product', 'items.serial']);
    }
    public function store(Request $r, AuditService $audit)
    {
        $d = $r->validate(['customer_id' => 'nullable|exists:customers,id', 'sales_order_id' => 'nullable|exists:sales_orders,id', 'warehouse_id' => 'required|exists:warehouses,id', 'reason' => ['required', Rule::in(['DEFECT', 'DOA', 'WRONG_ITEM', 'CUSTOMER_REQUEST', 'TRANSPORT_DAMAGE', 'OTHER'])], 'notes' => 'nullable|string', 'items' => 'required|array|min:1', 'items.*.product_serial_id' => 'required|distinct|exists:product_serials,id', 'items.*.item_reason' => 'nullable|string|max:100']);
        return DB::transaction(function () use ($d, $r, $audit) {
            $ret = CustomerReturn::create(['return_number' => 'RT-' . now()->format('Ymd-His') . '-' . random_int(100, 999), 'customer_id' => $d['customer_id'] ?? null, 'sales_order_id' => $d['sales_order_id'] ?? null, 'warehouse_id' => $d['warehouse_id'], 'status' => 'RECEIVED', 'reason' => $d['reason'], 'notes' => $d['notes'] ?? null, 'created_by' => $r->user()->id, 'received_at' => now()]);
            foreach ($d['items'] as $x) {
                $serial = ProductSerial::lockForUpdate()->findOrFail($x['product_serial_id']);
                abort_unless($serial->status === 'SOLD', 422, "TV serial {$serial->serial_number} is not in SOLD status.");
                $serial->update(['warehouse_id' => $d['warehouse_id'], 'warehouse_location_id' => null, 'status' => 'RETURNED', 'condition' => 'RETURNED']);
                DeviceEvent::create(['product_serial_id' => $serial->id, 'event_type' => 'RETURNED', 'to_warehouse_id' => $d['warehouse_id'], 'reference_type' => 'CUSTOMER_RETURN', 'reference_id' => $ret->id, 'performed_by' => $r->user()->id, 'occurred_at' => now()]);
                $ret->items()->create(['product_id' => $serial->product_id, 'product_serial_id' => $serial->id, 'quantity' => 1, 'item_reason' => $x['item_reason'] ?? null, 'disposition' => 'PENDING']);
            }$audit->log($r, 'RETURN_RECEIVED', $ret);
            return response()->json($ret->load(['items.product', 'items.serial', 'customer', 'warehouse']), 201);
        });
    }
    public function inspect(Request $r, CustomerReturn $customerReturn, InventoryService $inventory, AuditService $audit)
    {
        $d = $r->validate(['items' => 'required|array|min:1', 'items.*.item_id' => 'required|integer', 'items.*.disposition' => ['required', Rule::in(['RESTOCK', 'REPAIR', 'SCRAP'])], 'items.*.inspection_note' => 'nullable|string']);
        return DB::transaction(function () use ($d, $r, $customerReturn, $inventory, $audit) {
            abort_unless($customerReturn->status === 'RECEIVED', 422, 'Return already inspected.');
            $customerReturn->load('items.product');
            foreach ($d['items'] as $x) {
                $item = $customerReturn->items->firstWhere('id', $x['item_id']);
                abort_unless($item, 422, 'Invalid return item.');
                $serial = $item->product_serial_id ? ProductSerial::lockForUpdate()->findOrFail($item->product_serial_id) : null;
                if ($x['disposition'] === 'RESTOCK') {
                    $inventory->receive($customerReturn->warehouse_id, $item->product_id, (float) $item->quantity, (float) $item->product->cost_price, 'CUSTOMER_RETURN', $customerReturn->id, $r->user()->id, 'Inspected return restored to saleable stock');
                    if ($serial) {
                        $serial->update(['status' => 'IN_STOCK', 'condition' => 'OPEN_BOX']);
                        DeviceEvent::create(['product_serial_id' => $serial->id, 'event_type' => 'RESTOCKED', 'to_warehouse_id' => $customerReturn->warehouse_id, 'reference_type' => 'CUSTOMER_RETURN', 'reference_id' => $customerReturn->id, 'performed_by' => $r->user()->id, 'occurred_at' => now()]);
                    }
                } elseif ($x['disposition'] === 'REPAIR') {
                    if ($serial) {
                        $serial->update(['status' => 'REPAIR', 'condition' => 'REPAIR']);
                        DeviceEvent::create(['product_serial_id' => $serial->id, 'event_type' => 'SENT_TO_REPAIR', 'to_warehouse_id' => $customerReturn->warehouse_id, 'reference_type' => 'CUSTOMER_RETURN', 'reference_id' => $customerReturn->id, 'performed_by' => $r->user()->id, 'occurred_at' => now()]);
                    }
                } else {
                    if ($serial) {
                        $serial->update(['status' => 'DEFECTIVE', 'condition' => 'DEFECTIVE']);
                        DeviceEvent::create(['product_serial_id' => $serial->id, 'event_type' => 'SCRAPPED', 'to_warehouse_id' => $customerReturn->warehouse_id, 'reference_type' => 'CUSTOMER_RETURN', 'reference_id' => $customerReturn->id, 'performed_by' => $r->user()->id, 'occurred_at' => now()]);
                    }
                }$item->update(['disposition' => $x['disposition'], 'inspection_note' => $x['inspection_note'] ?? null]);
            }$customerReturn->update(['status' => 'INSPECTED', 'inspected_by' => $r->user()->id, 'inspected_at' => now()]);
            $audit->log($r, 'RETURN_INSPECTED', $customerReturn);
            return $customerReturn->fresh(['items.product', 'items.serial', 'customer', 'warehouse']);
        });
    }
}
