<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StockMovement extends Model
{
    public $timestamps = false;
    protected $fillable = ['warehouse_id', 'product_id', 'type', 'quantity', 'unit_cost', 'reference_type', 'reference_id', 'note', 'performed_by', 'occurred_at'];
    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:2', 'occurred_at' => 'datetime'];
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
