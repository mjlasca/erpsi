<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('purchase_detail_id')->nullable()->constrained('purchase_details')->nullOnDelete();
            $table->foreignId('parent_lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            $table->string('code', 80)->unique();
            $table->string('lot_number', 80)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->decimal('quantity_initial', 14, 3);
            $table->decimal('quantity_available', 14, 3);
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->string('status', 20)->default('AVAILABLE');
            $table->date('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'status']);
            $table->index('lot_number');
            $table->index('serial_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_lots');
    }
};
