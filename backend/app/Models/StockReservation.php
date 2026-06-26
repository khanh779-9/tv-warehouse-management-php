<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StockReservation extends Model
{
    protected $fillable = ['sales_order_id', 'sales_order_item_id', 'warehouse_id', 'product_id', 'quantity', 'status', 'expires_at', 'created_by', 'released_at'];
    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'expires_at' => 'datetime', 'released_at' => 'datetime'];
    }
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }
    public function item()
    {
        return $this->belongsTo(SalesOrderItem::class, 'sales_order_item_id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
