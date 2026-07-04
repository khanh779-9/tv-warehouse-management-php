<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseOrder extends Model
{
    protected $fillable = ['po_number', 'supplier_id', 'warehouse_id', 'status', 'approval_status', 'ordered_at', 'expected_at', 'notes', 'created_by', 'approved_by', 'approved_at'];
    protected function casts(): array
    {
        return ['ordered_at' => 'date', 'expected_at' => 'date', 'approved_at' => 'datetime'];
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
