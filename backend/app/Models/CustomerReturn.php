<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CustomerReturn extends Model
{
    protected $fillable = ['return_number', 'customer_id', 'sales_order_id', 'warehouse_id', 'status', 'reason', 'notes', 'created_by', 'inspected_by', 'received_at', 'inspected_at'];
    protected function casts(): array
    {
        return ['received_at' => 'datetime', 'inspected_at' => 'datetime'];
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function items()
    {
        return $this->hasMany(CustomerReturnItem::class);
    }
}
