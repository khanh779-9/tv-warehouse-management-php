<?php
use Illuminate\Support\Facades\Artisan;
Artisan::command('warehouse:hello', function(){ $this->info('Warehouse API is ready.'); });
