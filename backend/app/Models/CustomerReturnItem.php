<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CustomerReturnItem extends Model
{
    protected $fillable = ['customer_return_id', 'product_id', 'product_serial_id', 'quantity', 'item_reason', 'disposition', 'inspection_note'];
    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function serial()
    {
        return $this->belongsTo(ProductSerial::class, 'product_serial_id');
    }
}
