<?php
namespace Database\Seeders;

use App\Models\{Category,Customer,DeviceEvent,Product,ProductSerial,Stock,Supplier,User,Warehouse,WarehouseLocation,SalesOrder,StockReservation,WarrantyClaim};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
 public function run():void
 {
  $users=[];
  foreach([
   ['admin@warehouse.local','Portfolio Admin','admin'],['manager@warehouse.local','Warehouse Manager','manager'],
   ['staff@warehouse.local','Warehouse Staff','staff'],['viewer@warehouse.local','Read Only User','viewer']
  ] as [$email,$name,$role]) $users[$role]=User::updateOrCreate(['email'=>$email],['name'=>$name,'password'=>Hash::make('password'),'role'=>$role,'is_active'=>true]);

  $w1=Warehouse::firstOrCreate(['code'=>'HCM-DC'],['name'=>'HCM TV Distribution Center','address'=>'Thu Duc City, Ho Chi Minh City','phone'=>'028-1111-2222']);
  $w2=Warehouse::firstOrCreate(['code'=>'HCM-SOUTH'],['name'=>'HCM South TV Hub','address'=>'District 7, Ho Chi Minh City','phone'=>'028-2222-3333']);
  $w3=Warehouse::firstOrCreate(['code'=>'HCM-SERVICE'],['name'=>'HCM TV Service / Return Center','address'=>'Tan Phu, Ho Chi Minh City','phone'=>'028-3333-4444']);
  $loc43=WarehouseLocation::firstOrCreate(['warehouse_id'=>$w1->id,'code'=>'TV-43-A01'],['zone'=>'TV-43','aisle'=>'A','rack'=>'01','shelf'=>'FLOOR']);
  $loc55=WarehouseLocation::firstOrCreate(['warehouse_id'=>$w1->id,'code'=>'TV-55-B01'],['zone'=>'TV-55','aisle'=>'B','rack'=>'01','shelf'=>'FLOOR']);
  $loc65=WarehouseLocation::firstOrCreate(['warehouse_id'=>$w1->id,'code'=>'TV-65-C01'],['zone'=>'TV-65','aisle'=>'C','rack'=>'01','shelf'=>'FLOOR']);
  $loc75=WarehouseLocation::firstOrCreate(['warehouse_id'=>$w1->id,'code'=>'TV-75-D01'],['zone'=>'TV-75','aisle'=>'D','rack'=>'01','shelf'=>'FLOOR']);

  $led=Category::firstOrCreate(['name'=>'LED / 4K Smart TV'],['description'=>'Mainstream smart televisions']);
  $qled=Category::firstOrCreate(['name'=>'QLED / Mini LED TV'],['description'=>'High-brightness premium televisions']);
  $oled=Category::firstOrCreate(['name'=>'OLED TV'],['description'=>'Premium self-emissive panel televisions']);

  $supplier=Supplier::firstOrCreate(['code'=>'SUP-TV'],['name'=>'Regional TV Supply Co.','contact_name'=>'Nguyen An','email'=>'tv-supply@example.local','phone'=>'0900000001','address'=>'Ho Chi Minh City','tax_code'=>'0310000001']);
  $dealer=Customer::firstOrCreate(['code'=>'DLR-001'],['name'=>'Saigon Electronics Dealer','email'=>'buyer@dealer.local','phone'=>'0911000001','address'=>'District 1, HCMC']);
  Customer::firstOrCreate(['code'=>'STORE-002'],['name'=>'District 7 TV Experience Store','email'=>'ops@store.local','phone'=>'0911000002','address'=>'District 7, HCMC']);
  Customer::firstOrCreate(['code'=>'ECOM-003'],['name'=>'Online TV Sales Channel','email'=>'ecom@example.local','phone'=>'0911000003','address'=>'Ho Chi Minh City']);

  $catalog=[
   ['TV-A43-4K','NovaVision A43 Smart TV 4K',$led->id,'NovaVision','A43-4K','Black',43,'4K UHD','LED','Google TV',60,6200000,7990000,8,24,['hdr'=>['HDR10'],'hdmi_ports'=>3,'wifi'=>'Wi-Fi 5','bluetooth'=>'5.1']],
   ['TV-S50-4K','NovaVision S50 Smart TV 4K',$led->id,'NovaVision','S50-4K','Black',50,'4K UHD','LED','Google TV',60,7600000,9490000,6,24,['hdr'=>['HDR10','HLG'],'hdmi_ports'=>3,'speaker_w'=>20]],
   ['TV-Q55-120','NovaVision Q55 QLED 4K 120Hz',$qled->id,'NovaVision','Q55-120','Graphite',55,'4K UHD','QLED','Google TV',120,10400000,13990000,5,24,['hdr'=>['HDR10+','Dolby Vision'],'hdmi_ports'=>4,'hdmi_21'=>2,'vrr'=>true]],
   ['TV-M65-144','NovaVision M65 Mini LED 4K 144Hz',$qled->id,'NovaVision','M65-144','Black',65,'4K UHD','Mini LED','Google TV',144,16700000,21990000,4,24,['local_dimming_zones'=>384,'hdr'=>['HDR10+','Dolby Vision'],'hdmi_ports'=>4,'vrr'=>true]],
   ['TV-O65-120','NovaVision O65 OLED 4K 120Hz',$oled->id,'NovaVision','O65-120','Black',65,'4K UHD','OLED','Google TV',120,23800000,30990000,3,24,['hdr'=>['Dolby Vision','HDR10'],'hdmi_ports'=>4,'earc'=>true,'vrr'=>true]],
   ['TV-M75-144','NovaVision M75 Mini LED 4K 144Hz',$qled->id,'NovaVision','M75-144','Black',75,'4K UHD','Mini LED','Google TV',144,25900000,33990000,2,24,['local_dimming_zones'=>512,'hdr'=>['HDR10+','Dolby Vision'],'hdmi_ports'=>4,'vrr'=>true]],
  ];
  $products=[];
  foreach($catalog as $x){$products[$x[0]]=Product::updateOrCreate(['sku'=>$x[0]],[
   'barcode'=>'893'.str_pad((string)(100000+count($products)),9,'0',STR_PAD_LEFT),'name'=>$x[1],'category_id'=>$x[2],'brand'=>$x[3],'model_code'=>$x[4],'product_type'=>'TV','color'=>$x[5],
   'screen_size_inch'=>$x[6],'resolution'=>$x[7],'panel_type'=>$x[8],'operating_system'=>$x[9],'refresh_rate_hz'=>$x[10],'is_serialized'=>true,
   'cost_price'=>$x[11],'selling_price'=>$x[12],'min_stock'=>$x[13],'warranty_months'=>$x[14],'unit'=>'piece','is_active'=>true,'specs'=>$x[15],
   'description'=>"{$x[6]}-inch {$x[8]} television, {$x[7]}, {$x[10]}Hz"
  ]);}

  $balances=['TV-A43-4K'=>[18,6],'TV-S50-4K'=>[14,5],'TV-Q55-120'=>[12,4],'TV-M65-144'=>[8,3],'TV-O65-120'=>[5,2],'TV-M75-144'=>[4,1]];
  $locations=[43=>$loc43->id,50=>$loc55->id,55=>$loc55->id,65=>$loc65->id,75=>$loc75->id];
  foreach($balances as $sku=>[$main,$south]){
   $p=$products[$sku]; Stock::updateOrCreate(['warehouse_id'=>$w1->id,'product_id'=>$p->id],['quantity'=>$main,'reserved_quantity'=>0,'avg_cost'=>$p->cost_price]);
   Stock::updateOrCreate(['warehouse_id'=>$w2->id,'product_id'=>$p->id],['quantity'=>$south,'reserved_quantity'=>0,'avg_cost'=>$p->cost_price]);
   for($i=1;$i<=$main+$south;$i++){
    $wid=$i<=$main?$w1->id:$w2->id;
    ProductSerial::updateOrCreate(['serial_number'=>sprintf('%s-SN-%05d',$sku,$i)],[
      'product_id'=>$p->id,'warehouse_id'=>$wid,'warehouse_location_id'=>$wid===$w1->id?$locations[$p->screen_size_inch]:null,
      'condition'=>'NEW','status'=>'IN_STOCK','received_at'=>now()->subDays(20),
    ]);
   }
  }

  // Historical sold TV for warranty/service traceability. It is intentionally outside current stock balances.
  $sold=ProductSerial::updateOrCreate(['serial_number'=>'TV-Q55-120-SOLD-00001'],[
    'product_id'=>$products['TV-Q55-120']->id,'warehouse_id'=>$w3->id,'condition'=>'RETURNED','status'=>'REPAIR','received_at'=>now()->subMonths(3),'sold_at'=>now()->subMonth(),'warranty_start_at'=>today()->subMonth(),'warranty_end_at'=>today()->addMonths(23),
  ]);
  $claim=WarrantyClaim::firstOrCreate(['product_serial_id'=>$sold->id,'status'=>'DIAGNOSING'],['claim_number'=>'WC-TV-DEMO-001','customer_id'=>$dealer->id,'issue_description'=>'TV powers on but panel intermittently loses image.','diagnosis'=>'Pending panel/power-board verification','created_by'=>$users['staff']->id,'handled_by'=>$users['manager']->id,'received_at'=>now()->subDay()]);
  DeviceEvent::firstOrCreate(['product_serial_id'=>$sold->id,'event_type'=>'SOLD','reference_type'=>'HISTORICAL_SALE'],['from_warehouse_id'=>$w1->id,'reference_id'=>1,'performed_by'=>$users['staff']->id,'occurred_at'=>now()->subMonth()]);
  DeviceEvent::firstOrCreate(['product_serial_id'=>$sold->id,'event_type'=>'WARRANTY_OPENED','reference_type'=>'WARRANTY_CLAIM'],['to_warehouse_id'=>$w3->id,'reference_id'=>$claim->id,'performed_by'=>$users['staff']->id,'metadata'=>['status'=>'DIAGNOSING'],'occurred_at'=>now()->subDay()]);

  // Reserved dealer order to demonstrate On Hand / Reserved / Available.
  $so=SalesOrder::firstOrCreate(['so_number'=>'SO-TV-DEMO-RESERVED'],['customer_id'=>$dealer->id,'warehouse_id'=>$w1->id,'status'=>'CONFIRMED','channel'=>'DEALER','external_reference'=>'DEALER-TV-PO-2026-081','ordered_at'=>today(),'notes'=>'Dealer TV replenishment reservation','created_by'=>$users['staff']->id,'reserved_at'=>now()]);
  if($so->items()->count()===0){
   $item=$so->items()->create(['product_id'=>$products['TV-Q55-120']->id,'quantity'=>3,'issued_quantity'=>0,'unit_price'=>$products['TV-Q55-120']->selling_price]);
   Stock::where('warehouse_id',$w1->id)->where('product_id',$products['TV-Q55-120']->id)->increment('reserved_quantity',3);
   StockReservation::create(['sales_order_id'=>$so->id,'sales_order_item_id'=>$item->id,'warehouse_id'=>$w1->id,'product_id'=>$products['TV-Q55-120']->id,'quantity'=>3,'status'=>'ACTIVE','expires_at'=>now()->addDays(2),'created_by'=>$users['staff']->id]);
  }
 }
}
