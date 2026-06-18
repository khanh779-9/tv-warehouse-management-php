<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model
{
    protected $fillable = ['sku', 'barcode', 'name', 'brand', 'model_code', 'product_type', 'color', 'screen_size_inch', 'resolution', 'panel_type', 'operating_system', 'refresh_rate_hz', 'is_serialized', 'warranty_months', 'specs', 'category_id', 'unit', 'cost_price', 'selling_price', 'min_stock', 'description', 'is_active'];
    protected function casts(): array
    {
        return ['screen_size_inch' => 'integer', 'refresh_rate_hz' => 'integer', 'cost_price' => 'decimal:2', 'selling_price' => 'decimal:2', 'min_stock' => 'decimal:3', 'is_serialized' => 'boolean', 'warranty_months' => 'integer', 'specs' => 'array', 'is_active' => 'boolean'];
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
    public function serials()
    {
        return $this->hasMany(ProductSerial::class);
    }
}
