<?php
namespace App\Http\Controllers;
use App\Models\{ProductSerial, Stock, StockMovement};
use Illuminate\Http\Request;
class ReportController extends Controller
{
    public function valuation(Request $r)
    {
        return Stock::query()->join('products', 'products.id', '=', 'stocks.product_id')->join('warehouses', 'warehouses.id', '=', 'stocks.warehouse_id')->when($r->warehouse_id, fn($q, $v) => $q->where('stocks.warehouse_id', $v))->select('warehouses.code as warehouse', 'products.sku', 'products.name', 'products.brand', 'products.model_code', 'stocks.quantity', 'stocks.reserved_quantity', 'stocks.avg_cost')->selectRaw('(stocks.quantity - stocks.reserved_quantity) as available_quantity')->selectRaw('stocks.quantity * stocks.avg_cost as value')->orderBy('warehouses.code')->orderBy('products.sku')->get();
    }
    public function movementSummary(Request $r)
    {
        $from = $r->input('from', now()->subDays(30)->toDateString());
        $to = $r->input('to', now()->toDateString());
        return StockMovement::query()->whereBetween('occurred_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->select('type')->selectRaw('COUNT(*) movement_count, SUM(quantity) total_quantity, SUM(quantity*unit_cost) total_value')->groupBy('type')->get();
    }
    public function deviceStatus(Request $r)
    {
        return ProductSerial::query()->when($r->warehouse_id, fn($q, $v) => $q->where('warehouse_id', $v))->select('status', 'condition')->selectRaw('COUNT(*) device_count')->groupBy('status', 'condition')->orderBy('status')->get();
    }
}
