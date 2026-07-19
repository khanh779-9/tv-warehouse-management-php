<?php
namespace App\Http\Controllers;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class ProductController extends Controller
{
  public function index(Request $r)
  {
    return Product::with('category:id,name')->when($r->q, fn($q, $v) => $q->where(fn($x) => $x->where('sku', 'like', "%$v%")->orWhere('name', 'like', "%$v%")->orWhere('barcode', 'like', "%$v%")->orWhere('brand', 'like', "%$v%")->orWhere('model_code', 'like', "%$v%")))->orderBy('screen_size_inch')->orderBy('name')->paginate($r->integer('per_page', 20));
  }
  public function store(Request $r)
  {
    $data = $this->validated($r);
    $data['product_type'] = 'TV';
    $data['is_serialized'] = true;
    return response()->json(Product::create($data), 201);
  }
  public function show(Product $product)
  {
    return $product->load(['category', 'stocks.warehouse', 'serials' => fn($q) => $q->latest()->limit(50)]);
  }
  public function update(Request $r, Product $product)
  {
    $data = $this->validated($r, $product->id);
    $data['product_type'] = 'TV';
    $data['is_serialized'] = true;
    $product->update($data);
    return $product->fresh('category');
  }
  public function destroy(Product $product)
  {
    $product->update(['is_active' => false]);
    return response()->noContent();
  }
  private function validated(Request $r, ?int $id = null)
  {
    return $r->validate([
      'sku' => ['required', 'string', 'max:60', Rule::unique('products', 'sku')->ignore($id)],
      'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($id)],
      'name' => 'required|string|max:180',
      'brand' => 'required|string|max:80',
      'model_code' => 'required|string|max:80',
      'color' => 'nullable|string|max:60',
      'screen_size_inch' => 'required|integer|min:24|max:120',
      'resolution' => 'required|string|max:40',
      'panel_type' => 'required|string|max:40',
      'operating_system' => 'nullable|string|max:80',
      'refresh_rate_hz' => 'required|integer|min:50|max:240',
      'warranty_months' => 'required|integer|min:0|max:60',
      'specs' => 'nullable|array',
      'category_id' => 'nullable|exists:categories,id',
      'unit' => 'required|string|max:30',
      'cost_price' => 'required|numeric|min:0',
      'selling_price' => 'required|numeric|min:0',
      'min_stock' => 'required|numeric|min:0',
      'description' => 'nullable|string',
      'is_active' => 'sometimes|boolean'
    ]);
  }
}
