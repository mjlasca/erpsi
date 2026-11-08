<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Resources\ProductResource;
use App\Modules\Inventory\Actions\RecordInventoryMovement;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Requests\InventoryMovementRequest;
use App\Modules\Inventory\Resources\InventoryLotResource;
use App\Modules\Inventory\Resources\InventoryMovementResource;
use App\Modules\Inventory\Resources\WarehouseResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InventoryMovementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', InventoryMovement::class);

        $movements = InventoryMovement::query()
            ->with(['product', 'variant', 'warehouse', 'lot', 'creator'])
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->whereHas('product', function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('warehouse_id'), fn ($query, int $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($request->filled('movement_type'), fn ($query) => $query->where('movement_type', $request->string('movement_type')->value()))
            ->when($request->date('from'), fn ($query, $from) => $query->where('movement_date', '>=', $from->startOfDay()))
            ->when($request->date('to'), fn ($query, $to) => $query->where('movement_date', '<=', $to->endOfDay()))
            ->latest('movement_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('inventory/movements/index', [
            'movements' => InventoryMovementResource::collection($movements)->resolve(),
            'filters' => $request->only('search', 'warehouse_id', 'movement_type', 'from', 'to'),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name', 'code']),
            'movementTypes' => MovementType::options(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', InventoryMovement::class);

        return Inertia::render('inventory/movements/create', [
            'products' => ProductResource::collection(
                Product::query()->with('variants')->active()->orderBy('name')->get(),
            )->resolve(),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name', 'code']),
            'lots' => InventoryLotResource::collection(
                InventoryLot::query()->with(['product', 'variant', 'warehouse'])->available()->orderBy('code')->get(),
            )->resolve(),
            'movementTypes' => array_values(array_filter(
                MovementType::manualOptions(),
                fn (array $option): bool => in_array($option['value'], [MovementType::Return->value, MovementType::Adjustment->value], true),
            )),
            'selectedProductId' => $request->integer('product_id') ?: null,
        ]);
    }

    public function store(InventoryMovementRequest $request, RecordInventoryMovement $recordMovement): RedirectResponse
    {
        $this->authorize('create', InventoryMovement::class);

        $product = Product::query()->findOrFail($request->integer('product_id'));
        $variant = $request->filled('product_variant_id')
            ? ProductVariant::query()->findOrFail($request->integer('product_variant_id'))
            : null;
        $warehouse = Warehouse::query()->findOrFail($request->integer('warehouse_id'));
        $lot = $request->filled('inventory_lot_id')
            ? InventoryLot::query()->findOrFail($request->integer('inventory_lot_id'))
            : null;

        if ($product->track_lots && $lot === null) {
            throw ValidationException::withMessages([
                'inventory_lot_id' => 'Debes seleccionar un lote para este producto.',
            ]);
        }

        $recordMovement->execute(
            product: $product,
            warehouse: $warehouse,
            movementType: MovementType::from($request->string('movement_type')->value()),
            quantity: (string) $request->input('quantity'),
            variant: $variant,
            lot: $lot,
            unitCost: $request->filled('unit_cost') ? (string) $request->input('unit_cost') : null,
            notes: $request->input('notes'),
            user: $request->user(),
            movementDate: $request->date('movement_date'),
        );

        return redirect()
            ->route('inventory.movements.index')
            ->with('success', 'Movimiento registrado correctamente.');
    }

    public function show(InventoryMovement $movement): Response
    {
        $this->authorize('view', $movement);

        $movement->load(['product', 'variant', 'warehouse', 'lot', 'creator']);

        return Inertia::render('inventory/movements/show', [
            'movement' => (new InventoryMovementResource($movement))->resolve(),
            'warehouse' => new WarehouseResource($movement->warehouse),
        ]);
    }
}
