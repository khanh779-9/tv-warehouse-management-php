<?php
use Illuminate\Support\Facades\Route;
Route::get('/', fn()=>response()->json(['name'=>'Warehouse API','status'=>'ok']));
