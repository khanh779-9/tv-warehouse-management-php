<?php
namespace App\Http\Controllers;
use App\Models\StockReservation;
use Illuminate\Http\Request;
class ReservationController extends Controller
{
  public function index(Request $r)
  {
    return StockReservation::with(['salesOrder:id,so_number,status,channel', 'product:id,sku,name,brand,model_code', 'warehouse:id,code,name'])
      ->when($r->status, fn($q, $v) => $q->where('status', $v))->when($r->warehouse_id, fn($q, $v) => $q->where('warehouse_id', $v))->latest()->paginate($r->integer('per_page', 50));
  }
}
