<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
 AuthController,DashboardController,ProductController,MasterDataController,StockController,PurchaseOrderController,
 SalesOrderController,TransferController,StockCountController,ReportController,SerialController,ReservationController,
 WarehouseLocationController,CustomerReturnController,WarrantyClaimController,AuditLogController
};

Route::post('/auth/login',[AuthController::class,'login']);
Route::middleware('auth:sanctum')->group(function(){
 Route::get('/auth/me',[AuthController::class,'me']); Route::post('/auth/logout',[AuthController::class,'logout']);
 Route::get('/dashboard',DashboardController::class);
 Route::get('/products',[ProductController::class,'index']); Route::get('/products/{product}',[ProductController::class,'show']);
 Route::get('/stocks',[StockController::class,'index']); Route::get('/stock-movements',[StockController::class,'movements']);
 Route::get('/serials',[SerialController::class,'index']); Route::get('/serials/lookup',[SerialController::class,'lookup']); Route::get('/serials/{serial}',[SerialController::class,'show']);
 Route::get('/reservations',[ReservationController::class,'index']);
 Route::get('/warehouse-locations',[WarehouseLocationController::class,'index']);
 Route::get('/master/{type}',[MasterDataController::class,'index'])->whereIn('type',['warehouses','categories','suppliers','customers']);
 Route::get('/purchase-orders',[PurchaseOrderController::class,'index']); Route::get('/purchase-orders/{purchaseOrder}',[PurchaseOrderController::class,'show']);
 Route::get('/sales-orders',[SalesOrderController::class,'index']); Route::get('/sales-orders/{salesOrder}',[SalesOrderController::class,'show']);
 Route::get('/transfers',[TransferController::class,'index']); Route::get('/transfers/{transfer}',[TransferController::class,'show']);
 Route::get('/stock-counts',[StockCountController::class,'index']); Route::get('/stock-counts/{stockCount}',[StockCountController::class,'show']);
 Route::get('/returns',[CustomerReturnController::class,'index']); Route::get('/returns/{customerReturn}',[CustomerReturnController::class,'show']);
 Route::get('/warranty-claims',[WarrantyClaimController::class,'index']); Route::get('/warranty-claims/{warrantyClaim}',[WarrantyClaimController::class,'show']);
 Route::get('/reports/valuation',[ReportController::class,'valuation']); Route::get('/reports/movement-summary',[ReportController::class,'movementSummary']); Route::get('/reports/device-status',[ReportController::class,'deviceStatus']);

 Route::middleware('role:admin,manager,staff')->group(function(){
   Route::post('/purchase-orders',[PurchaseOrderController::class,'store']); Route::post('/purchase-orders/{purchaseOrder}/receive',[PurchaseOrderController::class,'receive']);
   Route::post('/sales-orders',[SalesOrderController::class,'store']); Route::post('/sales-orders/{salesOrder}/issue',[SalesOrderController::class,'issue']); Route::post('/sales-orders/{salesOrder}/cancel',[SalesOrderController::class,'cancel']);
   Route::post('/transfers',[TransferController::class,'store']); Route::post('/transfers/{transfer}/complete',[TransferController::class,'complete']);
   Route::post('/stock-counts',[StockCountController::class,'store']); Route::put('/stock-counts/{stockCount}/items',[StockCountController::class,'updateItems']); Route::post('/stock-counts/{stockCount}/finalize',[StockCountController::class,'finalize']);
   Route::post('/returns',[CustomerReturnController::class,'store']); Route::post('/returns/{customerReturn}/inspect',[CustomerReturnController::class,'inspect']);
   Route::post('/warranty-claims',[WarrantyClaimController::class,'store']); Route::put('/warranty-claims/{warrantyClaim}',[WarrantyClaimController::class,'update']);
 });
 Route::middleware('role:admin,manager')->group(function(){
   Route::post('/purchase-orders/{purchaseOrder}/approve',[PurchaseOrderController::class,'approve']);
   Route::post('/transfers/{transfer}/approve',[TransferController::class,'approve']);
   Route::post('/products',[ProductController::class,'store']); Route::put('/products/{product}',[ProductController::class,'update']); Route::delete('/products/{product}',[ProductController::class,'destroy']);
   Route::post('/master/{type}',[MasterDataController::class,'store'])->whereIn('type',['warehouses','categories','suppliers','customers']);
   Route::put('/master/{type}/{id}',[MasterDataController::class,'update'])->whereIn('type',['warehouses','categories','suppliers','customers']);
   Route::delete('/master/{type}/{id}',[MasterDataController::class,'destroy'])->whereIn('type',['warehouses','categories','suppliers','customers']);
   Route::post('/warehouse-locations',[WarehouseLocationController::class,'store']); Route::put('/warehouse-locations/{location}',[WarehouseLocationController::class,'update']);
   Route::get('/audit-logs',[AuditLogController::class,'index']);
 });
});
