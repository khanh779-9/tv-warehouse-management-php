<?php
namespace App\Http\Controllers;
use App\Models\StockTransfer;
use App\Services\{AuditService, InventoryService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class TransferController extends Controller
{
    public function index()
    {
        return StockTransfer::with(['fromWarehouse:id,code,name', 'toWarehouse:id,code,name', 'approver:id,name'])->withCount('items')->latest()->paginate(20);
    }
    public function store(Request $r, AuditService $audit)
    {
        $d = $r->validate(['from_warehouse_id' => 'required|different:to_warehouse_id|exists:warehouses,id', 'to_warehouse_id' => 'required|exists:warehouses,id', 'notes' => 'nullable|string', 'items' => 'required|array|min:1', 'items.*.product_id' => 'required|exists:products,id', 'items.*.quantity' => 'required|numeric|gt:0']);
        return DB::transaction(function () use ($d, $r, $audit) {
            $t = StockTransfer::create(['transfer_number' => 'TR-' . now()->format('Ymd-His') . '-' . random_int(100, 999), 'from_warehouse_id' => $d['from_warehouse_id'], 'to_warehouse_id' => $d['to_warehouse_id'], 'status' => 'DRAFT', 'approval_status' => 'PENDING', 'notes' => $d['notes'] ?? null, 'created_by' => $r->user()->id]);
            foreach ($d['items'] as $i)
                $t->items()->create($i);
            $audit->log($r, 'TRANSFER_CREATED', $t);
            return response()->json($t->load(['items.product', 'fromWarehouse', 'toWarehouse']), 201);
        });
    }
    public function show(StockTransfer $transfer)
    {
        return $transfer->load(['items.product', 'fromWarehouse', 'toWarehouse', 'approver:id,name']);
    }
    public function approve(Request $r, StockTransfer $transfer, AuditService $audit)
    {
        abort_unless($transfer->approval_status === 'PENDING', 422, 'Only pending transfers can be approved.');
        $transfer->update(['approval_status' => 'APPROVED', 'approved_by' => $r->user()->id, 'approved_at' => now()]);
        $audit->log($r, 'TRANSFER_APPROVED', $transfer);
        return $transfer->fresh(['approver']);
    }
    public function complete(Request $r, StockTransfer $transfer, InventoryService $inventory, AuditService $audit)
    {
        return DB::transaction(function () use ($r, $transfer, $inventory, $audit) {
            abort_unless($transfer->status === 'DRAFT', 422, 'Only draft transfer can be completed.');
            abort_unless($transfer->approval_status === 'APPROVED', 422, 'Transfer must be approved first.');
            $transfer->load('items');
            foreach ($transfer->items as $i)
                $inventory->transfer($transfer->from_warehouse_id, $transfer->to_warehouse_id, $i->product_id, (float) $i->quantity, 'STOCK_TRANSFER', $transfer->id, $r->user()->id);
            $transfer->update(['status' => 'COMPLETED', 'completed_at' => now()]);
            $audit->log($r, 'TRANSFER_COMPLETED', $transfer);
            return $transfer->fresh(['items.product', 'fromWarehouse', 'toWarehouse']);
        });
    }
}
