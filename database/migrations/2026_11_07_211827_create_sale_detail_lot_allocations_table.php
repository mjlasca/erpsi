<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_detail_lot_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_detail_id')->constrained('sale_details')->cascadeOnDelete();
            $table->foreignId('inventory_lot_id')->constrained('inventory_lots')->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['sale_detail_id', 'inventory_lot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_detail_lot_allocations');
    }
};
