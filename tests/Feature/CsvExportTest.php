<?php

use App\Jobs\GenerateExport;
use App\Models\Export;
use App\Modules\Cash\Actions\CloseCashSession;
use App\Modules\Cash\Actions\OpenCashSession;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Actions\RecordInventoryMovement;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Models\Sale;
use App\Support\Exports\ExportBuilder;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Storage;

it('exports products as a localized csv', function () {
    $tenant = makeTenant('export-products');
    $user = makeTenantUser($tenant);
    app(TenantContext::class)->set($tenant);
    app()->setLocale('es');

    Product::factory()->create(['name' => 'Producto exportable']);

    $response = $this->actingAs($user)->get(route('catalog.products.export'));

    $response->assertOk();
    expect((string) $response->headers->get('content-disposition'))->toContain('productos-');

    $content = $response->streamedContent();

    expect($content)->toContain('SKU')
        ->and($content)->toContain('Producto')
        ->and($content)->toContain('Producto exportable');
});

it('exports sales and respects the active tenant', function () {
    $tenantA = makeTenant('export-sales-a');
    $tenantB = makeTenant('export-sales-b');
    $user = makeTenantUser($tenantA);

    app(TenantContext::class)->run($tenantA, function (): void {
        $customer = Customer::factory()->create(['first_name' => 'Cliente', 'last_name' => 'Alfa']);
        Sale::factory()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-ALPHA-001',
        ]);
    });

    app(TenantContext::class)->run($tenantB, function (): void {
        $customer = Customer::factory()->create(['first_name' => 'Cliente', 'last_name' => 'Beta']);
        Sale::factory()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-BETA-001',
        ]);
    });

    $response = $this->actingAs($user)->get(route('sales.sales.export'));

    $response->assertOk();
    expect((string) $response->headers->get('content-disposition'))->toContain('ventas-');

    $content = $response->streamedContent();

    expect($content)->toContain('INV-ALPHA-001')
        ->and($content)->not->toContain('INV-BETA-001');
});

it('generates large exports in a queued job and stores them privately', function () {
    Storage::fake('local');

    $tenant = makeTenant('queued-export');
    $user = makeTenantUser($tenant);
    app(TenantContext::class)->set($tenant);
    Product::factory()->create(['name' => 'Producto en cola']);

    $export = Export::query()->create([
        'user_id' => $user->id,
        'type' => 'products',
        'status' => 'PENDING',
        'filters' => [],
        'filename' => 'pending.csv',
    ]);

    (new GenerateExport($export->id))->handle(app(ExportBuilder::class));

    $export->refresh();

    expect($export->status)->toBe('READY');
    expect($export->path)->not->toBeNull();
    Storage::disk('local')->assertExists($export->path);
    expect(Storage::disk('local')->get($export->path))
        ->toContain('Producto en cola');
});

it('exports cash sessions and their stock differences', function () {
    $tenant = makeTenant('export-cash');
    $user = makeTenantUser($tenant);
    app(TenantContext::class)->set($tenant);

    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create(['name' => 'Producto caja']);
    InventoryStock::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity_on_hand' => '4.000',
    ]);

    $session = app(OpenCashSession::class)->execute([
        'warehouse_id' => $warehouse->id,
        'opening_balance' => '25.00',
    ], $user);

    app(RecordInventoryMovement::class)->execute(
        product: $product,
        warehouse: $warehouse,
        movementType: MovementType::Adjustment,
        quantity: '-1.000',
        user: $user,
    );
    app(CloseCashSession::class)->execute($session, '25.00', $user);

    $summary = $this->actingAs($user)->get(route('cash.sessions.export', $session));
    $summary->assertOk();
    $summary->assertDownload('arqueo-'.$session->session_number.'.csv');
    expect($summary->streamedContent())->toContain($session->session_number);

    $differences = $this->actingAs($user)->get(route('cash.sessions.export-differences', $session));
    $differences->assertOk();
    $differences->assertDownload('diferencias-'.$session->session_number.'.csv');
    expect($differences->streamedContent())->toContain('Producto caja');
});
