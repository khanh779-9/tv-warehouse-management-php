<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Stock extends Model
{
    protected $fillable = ['warehouse_id', 'product_id', 'quantity', 'reserved_quantity', 'avg_cost'];
    protected $appends = ['available_quantity'];
    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'reserved_quantity' => 'decimal:3', 'avg_cost' => 'decimal:2'];
    }
    public function getAvailableQuantityAttribute(): float
    {
        return max(0, (float) $this->quantity - (float) $this->reserved_quantity);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
