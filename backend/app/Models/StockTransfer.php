<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StockTransfer extends Model
{
    protected $fillable = ['transfer_number', 'from_warehouse_id', 'to_warehouse_id', 'status', 'approval_status', 'notes', 'created_by', 'approved_by', 'approved_at', 'completed_at'];
    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'completed_at' => 'datetime'];
    }
    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }
    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
