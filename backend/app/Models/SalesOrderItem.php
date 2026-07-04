<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SalesOrderItem extends Model
{
    protected $fillable = ['sales_order_id', 'product_id', 'quantity', 'issued_quantity', 'unit_price'];
    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'issued_quantity' => 'decimal:3', 'unit_price' => 'decimal:2'];
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }
    public function reservations()
    {
        return $this->hasMany(StockReservation::class);
    }
    public function serials()
    {
        return $this->hasMany(ProductSerial::class);
    }
}
