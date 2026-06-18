<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WarehouseLocation extends Model
{
    protected $fillable = ['warehouse_id', 'code', 'zone', 'aisle', 'rack', 'shelf', 'is_active'];
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function serials()
    {
        return $this->hasMany(ProductSerial::class);
    }
}
