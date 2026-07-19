<?php
namespace App\Http\Controllers;
use App\Models\{Warehouse, Category, Supplier, Customer};
use Illuminate\Http\Request;
class MasterDataController extends Controller
{
    private function model(string $type): string
    {
        return match ($type) { 'warehouses' => Warehouse::class, 'categories' => Category::class, 'suppliers' => Supplier::class, 'customers' => Customer::class, default => abort(404)};
    }
    public function index(Request $r, string $type)
    {
        $m = $this->model($type);
        return $m::query()->orderBy('name')->get();
    }
    public function store(Request $r, string $type)
    {
        $m = $this->model($type);
        return response()->json($m::create($this->validateData($r, $type)), 201);
    }
    public function update(Request $r, string $type, int $id)
    {
        $m = $this->model($type);
        $row = $m::findOrFail($id);
        $row->update($this->validateData($r, $type, true));
        return $row;
    }
    public function destroy(string $type, int $id)
    {
        $m = $this->model($type);
        $row = $m::findOrFail($id);
        if (in_array($type, ['warehouses', 'suppliers', 'customers']))
            $row->update(['is_active' => false]);
        else
            $row->delete();
        return response()->noContent();
    }
    private function validateData(Request $r, string $type, bool $partial = false): array
    {
        $prefix = $partial ? 'sometimes' : 'required';
        return match ($type) {
            'warehouses' => $r->validate(['code' => "$prefix|string|max:30", 'name' => "$prefix|string|max:120", 'address' => 'nullable|string|max:255', 'phone' => 'nullable|string|max:30', 'is_active' => 'sometimes|boolean']),
            'categories' => $r->validate(['name' => "$prefix|string|max:120", 'description' => 'nullable|string']),
            'suppliers' => $r->validate(['code' => "$prefix|string|max:30", 'name' => "$prefix|string|max:160", 'contact_name' => 'nullable|string|max:120', 'email' => 'nullable|email', 'phone' => 'nullable|string|max:30', 'address' => 'nullable|string|max:255', 'tax_code' => 'nullable|string|max:50', 'is_active' => 'sometimes|boolean']),
            'customers' => $r->validate(['code' => "$prefix|string|max:30", 'name' => "$prefix|string|max:160", 'email' => 'nullable|email', 'phone' => 'nullable|string|max:30', 'address' => 'nullable|string|max:255', 'is_active' => 'sometimes|boolean']),
        };
    }
}
