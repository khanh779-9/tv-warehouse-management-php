<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'before_values', 'after_values', 'ip_address', 'user_agent', 'created_at'];
    protected function casts(): array
    {
        return ['before_values' => 'array', 'after_values' => 'array', 'created_at' => 'datetime'];
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
