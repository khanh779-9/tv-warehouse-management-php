<?php
namespace App\Http\Controllers;
use App\Models\{Product, ProductSerial, Stock, StockMovement, PurchaseOrder, SalesOrder, Warehouse, WarrantyClaim, CustomerReturn, StockReservation};
class DashboardController extends Controller
{
  public function __invoke()
  {
    $low = Stock::query()->join('products', 'products.id', '=', 'stocks.product_id')->whereRaw('(stocks.quantity - stocks.reserved_quantity) <= products.min_stock')->where('products.is_active', 1)->count();
    $value = Stock::query()->selectRaw('COALESCE(SUM(quantity * avg_cost),0) value')->value('value');
    $recent = StockMovement::with(['product:id,sku,name,brand,model_code', 'warehouse:id,code,name', 'user:id,name'])->latest('occurred_at')->limit(10)->get();
    return response()->json([
      'summary' => [
        'products' => Product::where('is_active', 1)->count(),
        'warehouses' => Warehouse::where('is_active', 1)->count(),
        'low_stock' => $low,
        'stock_value' => (float) $value,
        'open_purchase_orders' => PurchaseOrder::whereIn('status', ['DRAFT', 'ORDERED', 'PARTIAL'])->count(),
        'open_sales_orders' => SalesOrder::whereIn('status', ['DRAFT', 'CONFIRMED', 'PARTIAL'])->count(),
        'serialized_devices' => ProductSerial::count(),
        'devices_in_stock' => ProductSerial::where('status', 'IN_STOCK')->count(),
        'active_reservations' => (float) StockReservation::where('status', 'ACTIVE')->sum('quantity'),
        'open_warranty_claims' => WarrantyClaim::whereNotIn('status', ['COMPLETED', 'REJECTED'])->count(),
        'pending_returns' => CustomerReturn::where('status', 'RECEIVED')->count(),
      ],
      'recent_movements' => $recent,
    ]);
  }
}
