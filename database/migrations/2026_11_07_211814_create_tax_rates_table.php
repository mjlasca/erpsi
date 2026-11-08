<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('rate', 8, 4)->default(0);
            $table->string('tax_type', 30)->nullable();
            $table->string('jurisdiction', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['name', 'jurisdiction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
