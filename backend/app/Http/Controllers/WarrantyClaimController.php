<?php
namespace App\Http\Controllers;
use App\Models\{DeviceEvent, ProductSerial, WarrantyClaim};
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class WarrantyClaimController extends Controller
{
    public function index(Request $r)
    {
        return WarrantyClaim::with(['customer:id,code,name', 'serial.product:id,sku,name,brand,model_code', 'handler:id,name'])->when($r->status, fn($q, $v) => $q->where('status', $v))->latest()->paginate(30);
    }
    public function show(WarrantyClaim $warrantyClaim)
    {
        return $warrantyClaim->load(['customer', 'serial.product', 'serial.salesOrderItem.salesOrder', 'handler']);
    }
    public function store(Request $r, AuditService $audit)
    {
        $d = $r->validate(['customer_id' => 'nullable|exists:customers,id', 'serial_code' => 'required|string|max:120', 'issue_description' => 'required|string|max:2000']);
        $serial = ProductSerial::where('serial_number', $d['serial_code'])->firstOrFail();
        abort_if($serial->warranty_end_at && today()->gt($serial->warranty_end_at), 422, 'TV warranty has expired.');
        $claim = WarrantyClaim::create(['claim_number' => 'WC-' . now()->format('Ymd-His') . '-' . random_int(100, 999), 'customer_id' => $d['customer_id'] ?? null, 'product_serial_id' => $serial->id, 'status' => 'RECEIVED', 'issue_description' => $d['issue_description'], 'created_by' => $r->user()->id, 'received_at' => now()]);
        DeviceEvent::create(['product_serial_id' => $serial->id, 'event_type' => 'WARRANTY_OPENED', 'reference_type' => 'WARRANTY_CLAIM', 'reference_id' => $claim->id, 'performed_by' => $r->user()->id, 'metadata' => ['status' => $claim->status], 'occurred_at' => now()]);
        $audit->log($r, 'WARRANTY_CREATED', $claim);
        return response()->json($claim->load(['serial.product', 'customer']), 201);
    }
    public function update(Request $r, WarrantyClaim $warrantyClaim, AuditService $audit)
    {
        $d = $r->validate(['status' => ['required', Rule::in(['RECEIVED', 'DIAGNOSING', 'REPAIRING', 'READY', 'COMPLETED', 'REJECTED'])], 'diagnosis' => 'nullable|string', 'resolution' => 'nullable|string']);
        $before = $warrantyClaim->toArray();
        $warrantyClaim->update($d + ['handled_by' => $r->user()->id, 'completed_at' => in_array($d['status'], ['COMPLETED', 'REJECTED']) ? now() : null]);
        DeviceEvent::create(['product_serial_id' => $warrantyClaim->product_serial_id, 'event_type' => 'WARRANTY_STATUS', 'reference_type' => 'WARRANTY_CLAIM', 'reference_id' => $warrantyClaim->id, 'performed_by' => $r->user()->id, 'metadata' => ['status' => $d['status']], 'occurred_at' => now()]);
        $audit->log($r, 'WARRANTY_UPDATED', $warrantyClaim, $before);
        return $warrantyClaim->fresh(['serial.product', 'customer', 'handler']);
    }
}
