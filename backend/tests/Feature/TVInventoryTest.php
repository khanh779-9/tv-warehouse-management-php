<?php
namespace Tests\Feature;

use App\Models\{Category,Product,SalesOrder,Stock,User,Warehouse};
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TVInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_reduces_available_stock_without_reducing_on_hand(): void
    {
        $user=User::create(['name'=>'Staff','email'=>'staff@test.local','password'=>'x','role'=>'staff','is_active'=>true]);
        $warehouse=Warehouse::create(['code'=>'WH-1','name'=>'Warehouse 1']);
        $category=Category::create(['name'=>'Smart TVs']);
        $product=Product::create(['sku'=>'TV-TEST-55','name'=>'Test TV 55','brand'=>'TestBrand','model_code'=>'T55','product_type'=>'TV','color'=>'Black','screen_size_inch'=>55,'resolution'=>'4K UHD','panel_type'=>'QLED','operating_system'=>'Google TV','refresh_rate_hz'=>120,'is_serialized'=>true,'warranty_months'=>24,'category_id'=>$category->id,'unit'=>'piece','cost_price'=>100,'selling_price'=>150,'min_stock'=>1]);
        Stock::create(['warehouse_id'=>$warehouse->id,'product_id'=>$product->id,'quantity'=>10,'reserved_quantity'=>0,'avg_cost'=>100]);
        $order=SalesOrder::create(['so_number'=>'SO-TEST','warehouse_id'=>$warehouse->id,'status'=>'CONFIRMED','channel'=>'DEALER','ordered_at'=>today(),'created_by'=>$user->id]);
        $item=$order->items()->create(['product_id'=>$product->id,'quantity'=>4,'issued_quantity'=>0,'unit_price'=>150]);

        app(InventoryService::class)->reserveSalesOrderItem($item,$user->id);

        $stock=Stock::first();
        $this->assertEquals(10.0,(float)$stock->quantity);
        $this->assertEquals(4.0,(float)$stock->reserved_quantity);
        $this->assertEquals(6.0,$stock->available_quantity);
    }
}
