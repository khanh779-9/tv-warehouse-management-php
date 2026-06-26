<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DeviceEvent extends Model
{
    public $timestamps = false;
    protected $fillable = ['product_serial_id', 'event_type', 'from_warehouse_id', 'to_warehouse_id', 'reference_type', 'reference_id', 'metadata', 'performed_by', 'occurred_at'];
    protected function casts(): array
    {
        return ['metadata' => 'array', 'occurred_at' => 'datetime'];
    }
    public function serial()
    {
        return $this->belongsTo(ProductSerial::class, 'product_serial_id');
    }
    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }
    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
