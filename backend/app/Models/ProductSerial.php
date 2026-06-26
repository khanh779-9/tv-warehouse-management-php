<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductSerial extends Model
{
    protected $fillable = ['product_id', 'warehouse_id', 'warehouse_location_id', 'serial_number', 'condition', 'status', 'purchase_order_item_id', 'sales_order_item_id', 'received_at', 'sold_at', 'warranty_start_at', 'warranty_end_at', 'notes'];
    protected function casts(): array
    {
        return ['received_at' => 'datetime', 'sold_at' => 'datetime', 'warranty_start_at' => 'date', 'warranty_end_at' => 'date'];
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }
    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }
    public function salesOrderItem()
    {
        return $this->belongsTo(SalesOrderItem::class);
    }
    public function events()
    {
        return $this->hasMany(DeviceEvent::class)->latest('occurred_at');
    }
}
