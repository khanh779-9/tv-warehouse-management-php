<?php
namespace App\Services;

use App\Models\{DeviceEvent,Product,ProductSerial,SalesOrderItem,Stock,StockMovement,StockReservation};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function receive(int $warehouseId, int $productId, float $qty, float $unitCost, string $referenceType, int $referenceId, int $userId, ?string $note=null): void
    {
        if ($qty <= 0) throw ValidationException::withMessages(['quantity'=>'Quantity must be greater than 0.']);
        $this->withLockedStock($warehouseId,$productId,function(Stock $stock) use($qty,$unitCost,$referenceType,$referenceId,$userId,$note){
            $oldQty=(float)$stock->quantity; $oldCost=(float)$stock->avg_cost; $newQty=$oldQty+$qty;
            $stock->quantity=$newQty;
            $stock->avg_cost=$newQty>0 ? (($oldQty*$oldCost)+($qty*$unitCost))/$newQty : $unitCost;
            $stock->save();
            $this->movement($stock,'IN',$qty,$unitCost,$referenceType,$referenceId,$userId,$note);
        });
    }

    public function receivePurchaseSerials(int $warehouseId, int $productId, int $purchaseOrderItemId, array $serials, int $userId): void
    {
        $product=Product::findOrFail($productId);
        if(!$product->is_serialized) return;
        foreach($serials as $s){
            $serial=ProductSerial::create([
                'product_id'=>$productId,
                'warehouse_id'=>$warehouseId,
                'warehouse_location_id'=>$s['warehouse_location_id']??null,
                'serial_number'=>$s['serial_number'],
                'condition'=>'NEW',
                'status'=>'IN_STOCK',
                'purchase_order_item_id'=>$purchaseOrderItemId,
                'received_at'=>now(),
                'notes'=>$s['notes']??null,
            ]);
            DeviceEvent::create(['product_serial_id'=>$serial->id,'event_type'=>'RECEIVED','to_warehouse_id'=>$warehouseId,'reference_type'=>'PURCHASE_ORDER_ITEM','reference_id'=>$purchaseOrderItemId,'performed_by'=>$userId,'occurred_at'=>now()]);
        }
    }

    public function reserveSalesOrderItem(SalesOrderItem $item, int $userId): StockReservation
    {
        $item->loadMissing('salesOrder');
        $qty=(float)$item->quantity-(float)$item->issued_quantity;
        if($qty<=0) throw ValidationException::withMessages(['quantity'=>'Nothing to reserve.']);
        return DB::transaction(function() use($item,$qty,$userId){
            $existing=StockReservation::where('sales_order_item_id',$item->id)->where('status','ACTIVE')->lockForUpdate()->first();
            if($existing) return $existing;
            $stock=$this->lockedStock($item->salesOrder->warehouse_id,$item->product_id);
            if($stock->available_quantity+0.0001<$qty){
                throw ValidationException::withMessages(['stock'=>"Insufficient available stock for product {$item->product_id}. On hand: {$stock->quantity}, reserved: {$stock->reserved_quantity}."]);
            }
            $stock->reserved_quantity=(float)$stock->reserved_quantity+$qty; $stock->save();
            return StockReservation::create([
                'sales_order_id'=>$item->sales_order_id,'sales_order_item_id'=>$item->id,'warehouse_id'=>$item->salesOrder->warehouse_id,
                'product_id'=>$item->product_id,'quantity'=>$qty,'status'=>'ACTIVE','expires_at'=>now()->addDays(2),'created_by'=>$userId,
            ]);
        });
    }

    public function issueSalesOrderItem(int $itemId, float $qty, array $serialIds, int $userId): void
    {
        if($qty<=0) throw ValidationException::withMessages(['quantity'=>'Quantity must be greater than 0.']);
        $item=SalesOrderItem::with(['salesOrder','product'])->findOrFail($itemId);
        $stock=$this->lockedStock($item->salesOrder->warehouse_id,$item->product_id);
        $reservation=StockReservation::where('sales_order_item_id',$item->id)->where('status','ACTIVE')->lockForUpdate()->first();
        if(!$reservation || (float)$reservation->quantity+0.0001<$qty) throw ValidationException::withMessages(['reservation'=>'Active reservation is missing or insufficient.']);
        if((float)$stock->quantity+0.0001<$qty) throw ValidationException::withMessages(['stock'=>'Physical stock is insufficient.']);

        if($item->product->is_serialized){
            if(abs($qty-round($qty))>0.0001) throw ValidationException::withMessages(['quantity'=>'TVs must use whole-unit quantities.']);
            if(count($serialIds)!==(int)round($qty)) throw ValidationException::withMessages(['serial_ids'=>'Select exactly one TV serial record per issued unit.']);
            $serials=ProductSerial::whereIn('id',$serialIds)->lockForUpdate()->get();
            if($serials->count()!==count(array_unique($serialIds))) throw ValidationException::withMessages(['serial_ids'=>'One or more serial records were not found.']);
            foreach($serials as $serial){
                if($serial->product_id!==$item->product_id || $serial->warehouse_id!==$item->salesOrder->warehouse_id || $serial->status!=='IN_STOCK')
                    throw ValidationException::withMessages(['serial_ids'=>"Serial {$serial->serial_number} is not available in the order warehouse."]);
                $serial->update([
                    'status'=>'SOLD','sales_order_item_id'=>$item->id,'sold_at'=>now(),
                    'warranty_start_at'=>today(),'warranty_end_at'=>today()->addMonths($item->product->warranty_months),
                ]);
                DeviceEvent::create(['product_serial_id'=>$serial->id,'event_type'=>'SOLD','from_warehouse_id'=>$item->salesOrder->warehouse_id,'reference_type'=>'SALES_ORDER_ITEM','reference_id'=>$item->id,'performed_by'=>$userId,'occurred_at'=>now()]);
            }
        }

        $stock->quantity=(float)$stock->quantity-$qty;
        $stock->reserved_quantity=max(0,(float)$stock->reserved_quantity-$qty);
        $stock->save();
        $remaining=(float)$reservation->quantity-$qty;
        $reservation->update(['quantity'=>max(0,$remaining),'status'=>$remaining<=0.0001?'CONSUMED':'ACTIVE','released_at'=>$remaining<=0.0001?now():null]);
        $this->movement($stock,'OUT',$qty,(float)$stock->avg_cost,'SALES_ORDER',$item->sales_order_id,$userId,'Fulfilled reserved sales order stock');
    }

    public function releaseSalesOrderReservations(int $salesOrderId): void
    {
        $reservations=StockReservation::where('sales_order_id',$salesOrderId)->where('status','ACTIVE')->lockForUpdate()->get();
        foreach($reservations as $reservation){
            $stock=$this->lockedStock($reservation->warehouse_id,$reservation->product_id);
            $stock->reserved_quantity=max(0,(float)$stock->reserved_quantity-(float)$reservation->quantity); $stock->save();
            $reservation->update(['status'=>'RELEASED','released_at'=>now()]);
        }
    }

    public function transfer(int $fromId,int $toId,int $productId,float $qty,string $referenceType,int $referenceId,int $userId): void
    {
        if($fromId===$toId) throw ValidationException::withMessages(['warehouse'=>'Source and destination warehouses must be different.']);
        if($qty<=0) throw ValidationException::withMessages(['quantity'=>'Quantity must be greater than 0.']);
        $ids=[$fromId,$toId]; sort($ids);
        $stocks=[]; foreach($ids as $wid)$stocks[$wid]=$this->lockedStock($wid,$productId);
        $from=$stocks[$fromId]; $to=$stocks[$toId];
        if($from->available_quantity+0.0001<$qty) throw ValidationException::withMessages(['stock'=>'Insufficient available source stock after reservations.']);
        $product=Product::findOrFail($productId);
        if($product->is_serialized){
            if(abs($qty-round($qty))>0.0001) throw ValidationException::withMessages(['quantity'=>'TVs must be transferred in whole units.']);
            $serials=ProductSerial::where('product_id',$productId)->where('warehouse_id',$fromId)->where('status','IN_STOCK')->orderBy('id')->lockForUpdate()->limit((int)round($qty))->get();
            if($serials->count()!==(int)round($qty)) throw ValidationException::withMessages(['serials'=>'Not enough available TV serial units in source warehouse.']);
            foreach($serials as $serial){
                $serial->update(['warehouse_id'=>$toId,'warehouse_location_id'=>null]);
                DeviceEvent::create(['product_serial_id'=>$serial->id,'event_type'=>'TRANSFERRED','from_warehouse_id'=>$fromId,'to_warehouse_id'=>$toId,'reference_type'=>$referenceType,'reference_id'=>$referenceId,'performed_by'=>$userId,'occurred_at'=>now()]);
            }
        }
        $cost=(float)$from->avg_cost;
        $from->quantity=(float)$from->quantity-$qty; $from->save();
        $oldTo=(float)$to->quantity; $newTo=$oldTo+$qty;
        $to->avg_cost=$newTo>0 ? (($oldTo*(float)$to->avg_cost)+($qty*$cost))/$newTo : $cost;
        $to->quantity=$newTo; $to->save();
        $this->movement($from,'TRANSFER_OUT',$qty,$cost,$referenceType,$referenceId,$userId,"Transfer to warehouse {$toId}");
        $this->movement($to,'TRANSFER_IN',$qty,$cost,$referenceType,$referenceId,$userId,"Transfer from warehouse {$fromId}");
    }

    public function adjust(int $warehouseId,int $productId,float $countedQty,string $referenceType,int $referenceId,int $userId): float
    {
        if($countedQty<0) throw ValidationException::withMessages(['counted_quantity'=>'Counted quantity cannot be negative.']);
        $difference=0; $product=Product::findOrFail($productId);
        $this->withLockedStock($warehouseId,$productId,function(Stock $stock) use($countedQty,$referenceType,$referenceId,$userId,&$difference,$product){
            $difference=$countedQty-(float)$stock->quantity;
            if(abs($difference)<0.0001) return;
            if($product->is_serialized) throw ValidationException::withMessages(['counted_quantity'=>'TV inventory must be reconciled by serial number, not by anonymous quantity adjustment.']);
            if($difference<0 && $countedQty<(float)$stock->reserved_quantity) throw ValidationException::withMessages(['counted_quantity'=>'Count cannot be below reserved quantity.']);
            $stock->quantity=$countedQty; $stock->save();
            $type=$difference>0?'ADJUSTMENT_POSITIVE':'ADJUSTMENT_NEGATIVE';
            $this->movement($stock,$type,abs($difference),(float)$stock->avg_cost,$referenceType,$referenceId,$userId,'Stock count adjustment');
        });
        return $difference;
    }

    private function withLockedStock(int $warehouseId,int $productId,callable $callback): void { $callback($this->lockedStock($warehouseId,$productId)); }
    private function lockedStock(int $warehouseId,int $productId): Stock
    {
        Stock::firstOrCreate(['warehouse_id'=>$warehouseId,'product_id'=>$productId],['quantity'=>0,'reserved_quantity'=>0,'avg_cost'=>0]);
        return Stock::where('warehouse_id',$warehouseId)->where('product_id',$productId)->lockForUpdate()->firstOrFail();
    }
    private function movement(Stock $stock,string $type,float $qty,float $cost,string $referenceType,int $referenceId,int $userId,?string $note): void
    {
        StockMovement::create(['warehouse_id'=>$stock->warehouse_id,'product_id'=>$stock->product_id,'type'=>$type,'quantity'=>$qty,'unit_cost'=>$cost,'reference_type'=>$referenceType,'reference_id'=>$referenceId,'note'=>$note,'performed_by'=>$userId,'occurred_at'=>now()]);
    }
}
