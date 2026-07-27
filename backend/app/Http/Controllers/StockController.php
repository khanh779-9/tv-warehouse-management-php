<?php
namespace App\Http\Controllers;
use App\Models\{Stock, StockMovement};
use Illuminate\Http\Request;
class StockController extends Controller
{
    public function index(Request $r)
    {
        return Stock::with(['product.category:id,name', 'warehouse:id,code,name'])->when($r->warehouse_id, fn($q, $v) => $q->where('warehouse_id', $v))->when($r->q, fn($q, $v) => $q->whereHas('product', fn($p) => $p->where('sku', 'like', "%$v%")->orWhere('name', 'like', "%$v%")->orWhere('brand', 'like', "%$v%")->orWhere('model_code', 'like', "%$v%")))->orderBy('warehouse_id')->paginate($r->integer('per_page', 30));
    }
    public function movements(Request $r)
    {
        return StockMovement::with(['product:id,sku,name,brand,model_code', 'warehouse:id,code,name', 'user:id,name'])->when($r->warehouse_id, fn($q, $v) => $q->where('warehouse_id', $v))->when($r->product_id, fn($q, $v) => $q->where('product_id', $v))->when($r->type, fn($q, $v) => $q->where('type', $v))->latest('occurred_at')->paginate($r->integer('per_page', 50));
    }
}
