<?php
namespace App\Http\Controllers;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class WarehouseLocationController extends Controller
{
    public function index(Request $r)
    {
        return WarehouseLocation::with('warehouse:id,code,name')->when($r->warehouse_id, fn($q, $v) => $q->where('warehouse_id', $v))->orderBy('warehouse_id')->orderBy('code')->get();
    }
    public function store(Request $r)
    {
        $d = $this->validated($r);
        return response()->json(WarehouseLocation::create($d), 201);
    }
    public function update(Request $r, WarehouseLocation $location)
    {
        $location->update($this->validated($r, $location->id));
        return $location->fresh('warehouse');
    }
    private function validated(Request $r, ?int $id = null)
    {
        return $r->validate(['warehouse_id' => 'required|exists:warehouses,id', 'code' => ['required', 'string', 'max:60', Rule::unique('warehouse_locations', 'code')->where(fn($q) => $q->where('warehouse_id', $r->warehouse_id))->ignore($id)], 'zone' => 'nullable|string|max:60', 'aisle' => 'nullable|string|max:40', 'rack' => 'nullable|string|max:40', 'shelf' => 'nullable|string|max:40', 'is_active' => 'sometimes|boolean']);
    }
}
