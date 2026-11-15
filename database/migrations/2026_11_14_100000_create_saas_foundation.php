<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tenantTables = [
        'roles',
        'units',
        'product_categories',
        'products',
        'product_variants',
        'product_attributes',
        'warehouses',
        'tax_rates',
        'suppliers',
        'customers',
        'purchases',
        'purchase_details',
        'sales',
        'sale_details',
        'inventory_stock',
        'inventory_lots',
        'inventory_movements',
        'sale_detail_lot_allocations',
        'payments',
        'payment_allocations',
        'cash_reconciliations',
    ];

    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 100)->unique();
            $table->string('legal_name', 200)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('locale', 5)->default('es');
            $table->string('timezone', 64)->default('UTC');
            $table->string('currency', 3)->default('MXN');
            $table->string('date_format', 20)->default('d/m/Y');
            $table->unsignedTinyInteger('week_starts_on')->default(1);
            $table->string('invoice_prefix', 20)->default('INV');
            $table->string('purchase_prefix', 20)->default('COMP');
            $table->string('sale_return_prefix', 20)->default('RDEV');
            $table->string('purchase_return_prefix', 20)->default('RCOMP');
            $table->string('cash_session_prefix', 20)->default('CAJA');
            $table->foreignId('default_warehouse_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
            $table->index(['user_id', 'is_active']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->boolean('is_platform_admin')->default(false);
        });

        foreach ($this->tenantTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->index();
            });
        }

        $this->replaceUniqueIndexes();

        Schema::table('inventory_stock', function (Blueprint $table) {
            $table->decimal('quantity_quarantined', 14, 3)->default(0);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('returned_amount', 14, 2)->default(0);
            $table->decimal('refunded_amount', 14, 2)->default(0);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('returned_amount', 14, 2)->default(0);
            $table->decimal('refunded_amount', 14, 2)->default(0);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('cash_session_id')->nullable()->index();
        });

        $this->createReturnsTables();
        $this->createCashSessionTables();
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_snapshot_lines');
        Schema::dropIfExists('inventory_snapshots');
        Schema::dropIfExists('cash_sessions');

        Schema::dropIfExists('purchase_return_details');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('sale_return_details');
        Schema::dropIfExists('sale_returns');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('cash_session_id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['returned_amount', 'refunded_amount']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['returned_amount', 'refunded_amount']);
        });

        Schema::table('inventory_stock', function (Blueprint $table) {
            $table->dropColumn('quantity_quarantined');
        });

        foreach ($this->tenantTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'timezone', 'is_platform_admin']);
        });

        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('tenants');
    }

    private function replaceUniqueIndexes(): void
    {
        $indexes = [
            ['roles', 'roles_name_unique', ['tenant_id', 'name']],
            ['units', 'units_abbreviation_unique', ['tenant_id', 'abbreviation']],
            ['products', 'products_sku_unique', ['tenant_id', 'sku']],
            ['products', 'products_barcode_unique', ['tenant_id', 'barcode']],
            ['product_variants', 'product_variants_sku_unique', ['tenant_id', 'sku']],
            ['product_variants', 'product_variants_barcode_unique', ['tenant_id', 'barcode']],
            ['warehouses', 'warehouses_code_unique', ['tenant_id', 'code']],
            ['suppliers', 'suppliers_tax_id_unique', ['tenant_id', 'tax_id']],
            ['customers', 'customers_tax_id_unique', ['tenant_id', 'tax_id']],
            ['purchases', 'purchases_purchase_number_unique', ['tenant_id', 'purchase_number']],
            ['sales', 'sales_invoice_number_unique', ['tenant_id', 'invoice_number']],
            ['inventory_lots', 'inventory_lots_code_unique', ['tenant_id', 'code']],
            ['cash_reconciliations', 'cash_reconciliations_date_unique', ['tenant_id', 'date']],
        ];

        foreach ($indexes as [$tableName, $indexName, $columns]) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName, $columns): void {
                try {
                    $table->dropUnique($indexName);
                } catch (Throwable) {
                    // The index may not exist on a partially customized database.
                }

                $table->unique($columns);
            });
        }
    }

    private function createReturnsTables(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->index();
            $table->foreignId('sale_id')->constrained('sales')->restrictOnDelete();
            $table->string('return_number', 50);
            $table->date('return_date');
            $table->string('status', 20)->default('DRAFT');
            $table->text('reason')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('credited_amount', 14, 2)->default(0);
            $table->decimal('refunded_amount', 14, 2)->default(0);
            $table->string('refund_method', 30)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'return_number']);
            $table->index(['sale_id', 'status']);
        });

        Schema::create('sale_return_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->index();
            $table->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
            $table->foreignId('sale_detail_id')->constrained('sale_details')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
            $table->foreignId('inventory_lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('disposition', 20)->default('RESTOCK');
            $table->timestamps();

            $table->index(['sale_detail_id', 'inventory_lot_id']);
        });

        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->index();
            $table->foreignId('purchase_id')->constrained('purchases')->restrictOnDelete();
            $table->string('return_number', 50);
            $table->date('return_date');
            $table->string('status', 20)->default('DRAFT');
            $table->text('reason')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('credited_amount', 14, 2)->default(0);
            $table->decimal('refunded_amount', 14, 2)->default(0);
            $table->string('refund_method', 30)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'return_number']);
            $table->index(['purchase_id', 'status']);
        });

        Schema::create('purchase_return_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->index();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('purchase_detail_id')->constrained('purchase_details')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
            $table->foreignId('inventory_lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['purchase_detail_id', 'inventory_lot_id'], 'purchase_return_details_lookup_index');
        });
    }

    private function createCashSessionTables(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->index();
            $table->string('session_number', 50);
            $table->date('business_date');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('status', 20)->default('OPEN');
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('expected_cash', 14, 2)->default(0);
            $table->decimal('counted_cash', 14, 2)->nullable();
            $table->decimal('difference', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'session_number']);
            $table->index(['business_date', 'status']);
        });

        Schema::create('inventory_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->index();
            $table->foreignId('cash_session_id')->constrained('cash_sessions')->cascadeOnDelete();
            $table->string('type', 20);
            $table->timestamp('captured_at');
            $table->foreignId('captured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['cash_session_id', 'type']);
        });

        Schema::create('inventory_snapshot_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->index();
            $table->foreignId('inventory_snapshot_id')->constrained('inventory_snapshots')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('inventory_lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            $table->decimal('quantity_on_hand', 14, 3)->default(0);
            $table->decimal('quantity_reserved', 14, 3)->default(0);
            $table->decimal('quantity_quarantined', 14, 3)->default(0);
            $table->decimal('quantity_available', 14, 3)->default(0);
            $table->timestamps();

            $table->index(
                ['inventory_snapshot_id', 'product_id', 'product_variant_id', 'warehouse_id'],
                'inventory_snapshot_lines_lookup_index',
            );
        });
    }
};
