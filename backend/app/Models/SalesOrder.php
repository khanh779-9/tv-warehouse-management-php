<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SalesOrder extends Model
{
    protected $fillable = ['so_number', 'customer_id', 'warehouse_id', 'status', 'channel', 'external_reference', 'ordered_at', 'notes', 'created_by', 'reserved_at'];
    protected function casts(): array
    {
        return ['ordered_at' => 'date', 'reserved_at' => 'datetime'];
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function items()
    {
        return $this->hasMany(SalesOrderItem::class);
    }
    public function reservations()
    {
        return $this->hasMany(StockReservation::class);
    }
}
