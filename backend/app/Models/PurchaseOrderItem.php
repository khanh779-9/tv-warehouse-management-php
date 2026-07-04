<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseOrderItem extends Model
{
    protected $fillable = ['purchase_order_id', 'product_id', 'quantity', 'received_quantity', 'unit_cost'];
    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'received_quantity' => 'decimal:3', 'unit_cost' => 'decimal:2'];
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
    public function serials()
    {
        return $this->hasMany(ProductSerial::class);
    }
}
