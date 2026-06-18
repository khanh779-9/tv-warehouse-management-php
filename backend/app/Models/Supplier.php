<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Supplier extends Model
{
    protected $fillable = ['code', 'name', 'contact_name', 'email', 'phone', 'address', 'tax_code', 'is_active'];
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
