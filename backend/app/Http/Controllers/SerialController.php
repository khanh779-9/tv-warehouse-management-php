<?php
namespace App\Http\Controllers;
use App\Models\ProductSerial;
use Illuminate\Http\Request;
class SerialController extends Controller
{
    public function index(Request $r)
    {
        return ProductSerial::with(['product:id,sku,name,brand,model_code,screen_size_inch,resolution,panel_type,warranty_months', 'warehouse:id,code,name', 'location:id,code,zone,aisle,rack,shelf'])
            ->when($r->q, fn($q, $v) => $q->where('serial_number', 'like', "%$v%"))
            ->when($r->warehouse_id, fn($q, $v) => $q->where('warehouse_id', $v))->when($r->product_id, fn($q, $v) => $q->where('product_id', $v))->when($r->status, fn($q, $v) => $q->where('status', $v))->when($r->condition, fn($q, $v) => $q->where('condition', $v))->latest()->paginate($r->integer('per_page', 50));
    }
    public function show(ProductSerial $serial)
    {
        return $serial->load(['product', 'warehouse', 'location', 'purchaseOrderItem.purchaseOrder', 'salesOrderItem.salesOrder', 'events.fromWarehouse:id,code,name', 'events.toWarehouse:id,code,name', 'events.user:id,name']);
    }
    public function lookup(Request $r)
    {
        $d = $r->validate(['code' => 'required|string|max:120']);
        return ProductSerial::with(['product', 'warehouse', 'location', 'salesOrderItem.salesOrder.customer'])->where('serial_number', $d['code'])->firstOrFail();
    }
}
