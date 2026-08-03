<?php
namespace App\Http\Controllers;
use App\Models\AuditLog;
use Illuminate\Http\Request;
class AuditLogController extends Controller
{
    public function index(Request $r)
    {
        return AuditLog::with('user:id,name,email')->when($r->action, fn($q, $v) => $q->where('action', $v))->when($r->entity_type, fn($q, $v) => $q->where('entity_type', $v))->latest('created_at')->paginate($r->integer('per_page', 50));
    }
}
