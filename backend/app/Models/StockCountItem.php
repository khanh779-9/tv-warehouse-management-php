<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StockCountItem extends Model
{
    protected $fillable = ['stock_count_id', 'product_id', 'system_quantity', 'counted_quantity', 'difference'];
    protected function casts(): array
    {
        return ['system_quantity' => 'decimal:3', 'counted_quantity' => 'decimal:3', 'difference' => 'decimal:3'];
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
