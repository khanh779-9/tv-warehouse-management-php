<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StockCount extends Model
{
    protected $fillable = ['count_number', 'warehouse_id', 'status', 'notes', 'created_by', 'finalized_at'];
    protected function casts(): array
    {
        return ['finalized_at' => 'datetime'];
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function items()
    {
        return $this->hasMany(StockCountItem::class);
    }
}
