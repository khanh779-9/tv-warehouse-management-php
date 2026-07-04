<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WarrantyClaim extends Model
{
    protected $fillable = ['claim_number', 'customer_id', 'product_serial_id', 'status', 'issue_description', 'diagnosis', 'resolution', 'created_by', 'handled_by', 'received_at', 'completed_at'];
    protected function casts(): array
    {
        return ['received_at' => 'datetime', 'completed_at' => 'datetime'];
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function serial()
    {
        return $this->belongsTo(ProductSerial::class, 'product_serial_id');
    }
    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
