<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->string('brand', 80)->after('name');
            $t->string('model_code', 80)->after('brand');
            $t->string('product_type', 40)->default('TV')->after('model_code');
            $t->string('color', 60)->nullable()->after('product_type');
            $t->unsignedSmallInteger('screen_size_inch')->after('color');
            $t->string('resolution', 40)->default('4K UHD')->after('screen_size_inch');
            $t->string('panel_type', 40)->default('LED')->after('resolution');
            $t->string('operating_system', 80)->nullable()->after('panel_type');
            $t->unsignedSmallInteger('refresh_rate_hz')->default(60)->after('operating_system');
            $t->boolean('is_serialized')->default(true)->after('refresh_rate_hz');
            $t->unsignedSmallInteger('warranty_months')->default(24)->after('is_serialized');
            $t->json('specs')->nullable()->after('warranty_months');
            $t->index(['brand', 'model_code']);
            $t->index(['product_type', 'is_serialized']);
        });

        Schema::table('stocks', function (Blueprint $t) {
            $t->decimal('reserved_quantity', 15, 3)->default(0)->after('quantity');
        });

        Schema::table('purchase_orders', function (Blueprint $t) {
            $t->string('approval_status', 30)->default('PENDING')->after('status');
            $t->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable()->after('approved_by');
        });

        Schema::table('sales_orders', function (Blueprint $t) {
            $t->string('channel', 40)->default('DEALER')->after('status');
            $t->string('external_reference', 100)->nullable()->after('channel');
            $t->timestamp('reserved_at')->nullable()->after('created_by');
        });

        Schema::table('stock_transfers', function (Blueprint $t) {
            $t->string('approval_status', 30)->default('PENDING')->after('status');
            $t->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable()->after('approved_by');
        });

        Schema::create('warehouse_locations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $t->string('code', 60);
            $t->string('zone', 60)->nullable();
            $t->string('aisle', 40)->nullable();
            $t->string('rack', 40)->nullable();
            $t->string('shelf', 40)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['warehouse_id', 'code']);
        });

        Schema::create('product_serials', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained();
            $t->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $t->string('serial_number', 120)->unique();
            $t->string('condition', 30)->default('NEW');
            $t->string('status', 30)->default('IN_STOCK');
            $t->foreignId('purchase_order_item_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('sales_order_item_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('received_at')->nullable();
            $t->timestamp('sold_at')->nullable();
            $t->date('warranty_start_at')->nullable();
            $t->date('warranty_end_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['product_id', 'warehouse_id', 'status']);
            $t->index(['condition', 'status']);
        });


        Schema::create('device_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_serial_id')->constrained()->cascadeOnDelete();
            $t->string('event_type', 40);
            $t->foreignId('from_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $t->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $t->string('reference_type', 60)->nullable();
            $t->unsignedBigInteger('reference_id')->nullable();
            $t->json('metadata')->nullable();
            $t->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('occurred_at')->useCurrent();
            $t->index(['product_serial_id', 'occurred_at']);
            $t->index(['reference_type', 'reference_id']);
        });

        Schema::create('stock_reservations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('sales_order_item_id')->constrained()->cascadeOnDelete();
            $t->foreignId('warehouse_id')->constrained();
            $t->foreignId('product_id')->constrained();
            $t->decimal('quantity', 15, 3);
            $t->string('status', 30)->default('ACTIVE');
            $t->timestamp('expires_at')->nullable();
            $t->foreignId('created_by')->constrained('users');
            $t->timestamp('released_at')->nullable();
            $t->timestamps();
            $t->index(['warehouse_id', 'product_id', 'status']);
            $t->index(['sales_order_id', 'status']);
        });

        Schema::create('customer_returns', function (Blueprint $t) {
            $t->id();
            $t->string('return_number', 60)->unique();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('warehouse_id')->constrained();
            $t->string('status', 30)->default('RECEIVED');
            $t->string('reason', 80);
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('received_at')->useCurrent();
            $t->timestamp('inspected_at')->nullable();
            $t->timestamps();
        });

        Schema::create('customer_return_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_return_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained();
            $t->foreignId('product_serial_id')->nullable()->constrained()->nullOnDelete();
            $t->decimal('quantity', 15, 3)->default(1);
            $t->string('item_reason', 100)->nullable();
            $t->string('disposition', 30)->default('PENDING');
            $t->text('inspection_note')->nullable();
            $t->timestamps();
        });

        Schema::create('warranty_claims', function (Blueprint $t) {
            $t->id();
            $t->string('claim_number', 60)->unique();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('product_serial_id')->constrained()->cascadeOnDelete();
            $t->string('status', 30)->default('RECEIVED');
            $t->text('issue_description');
            $t->text('diagnosis')->nullable();
            $t->text('resolution')->nullable();
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('received_at')->useCurrent();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'received_at']);
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action', 80);
            $t->string('entity_type', 100);
            $t->unsignedBigInteger('entity_id')->nullable();
            $t->json('before_values')->nullable();
            $t->json('after_values')->nullable();
            $t->string('ip_address', 64)->nullable();
            $t->string('user_agent', 500)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['entity_type', 'entity_id']);
            $t->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('warranty_claims');
        Schema::dropIfExists('customer_return_items');
        Schema::dropIfExists('customer_returns');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('device_events');
        Schema::dropIfExists('product_serials');
        Schema::dropIfExists('warehouse_locations');

        Schema::table('stock_transfers', function (Blueprint $t) {
            $t->dropForeign(['approved_by']);
            $t->dropColumn(['approval_status', 'approved_by', 'approved_at']);
        });
        Schema::table('sales_orders', function (Blueprint $t) {
            $t->dropColumn(['channel', 'external_reference', 'reserved_at']);
        });
        Schema::table('purchase_orders', function (Blueprint $t) {
            $t->dropForeign(['approved_by']);
            $t->dropColumn(['approval_status', 'approved_by', 'approved_at']);
        });
        Schema::table('stocks', fn (Blueprint $t) => $t->dropColumn('reserved_quantity'));
        Schema::table('products', function (Blueprint $t) {
            $t->dropColumn(['brand','model_code','product_type','color','screen_size_inch','resolution','panel_type','operating_system','refresh_rate_hz','is_serialized','warranty_months','specs']);
        });
    }
};
