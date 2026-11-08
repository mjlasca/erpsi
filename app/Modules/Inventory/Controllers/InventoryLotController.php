<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Resources\ProductResource;
use App\Modules\Inventory\Actions\RecordInventoryMovement;
use App\Modules\Inventory\Enums\LotStatus;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Requests\StoreInventoryLotRequest;
use App\Modules\Inventory\Requests\UpdateInventoryLotRequest;
use App\Modules\Inventory\Resources\InventoryLotResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InventoryLotController extends Controller
{
    public function search(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', InventoryLot::class);

        $lots = InventoryLot::query()
            ->with(['product', 'variant', 'warehouse'])
            ->available()
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('lot_number', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('product_id'), fn ($query, int $productId) => $query->where('product_id', $productId))
            ->when($request->integer('warehouse_id'), fn ($query, int $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->orderBy('code')
            ->limit(20)
            ->get();

        return InventoryLotResource::collection($lots);
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', InventoryLot::class);

        $lots = InventoryLot::query()
            ->with(['product', 'variant', 'warehouse'])
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('lot_number', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('product_id'), fn ($query, int $productId) => $query->where('product_id', $productId))
            ->when($request->integer('warehouse_id'), fn ($query, int $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Inventory/Lots/Index', [
            'lots' => $lots->through(fn ($lot): array => (new InventoryLotResource($lot))->resolve()),
            'filters' => $request->only('search', 'product_id', 'warehouse_id', 'status'),
            'products' => Product::query()->trackedByLots()->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name', 'code']),
            'statuses' => LotStatus::options(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', InventoryLot::class);

        return Inertia::render('Inventory/Lots/Create', [
            'products' => ProductResource::collection(
                Product::query()->with('variants')->trackedByLots()->active()->orderBy('name')->get(),
            )->resolve(),
            'warehouses' => Warehouse::query()->active()->orderBy('name')->get(['id', 'name', 'code']),
            'statuses' => LotStatus::options(),
            'selectedProductId' => $request->integer('product_id') ?: null,
        ]);
    }

    public function store(StoreInventoryLotRequest $request, RecordInventoryMovement $recordMovement): RedirectResponse
    {
        $this->authorize('create', InventoryLot::class);

        $product = Product::query()->findOrFail($request->integer('product_id'));

        if (! $product->track_lots) {
            throw ValidationException::withMessages([
                'product_id' => 'El producto seleccionado no maneja lotes.',
            ]);
        }

        $variant = $request->filled('product_variant_id')
            ? ProductVariant::query()->findOrFail($request->integer('product_variant_id'))
            : null;
        $warehouse = Warehouse::query()->findOrFail($request->integer('warehouse_id'));

        $lot = DB::transaction(function () use ($request, $recordMovement, $product, $variant, $warehouse): InventoryLot {
            $lot = InventoryLot::query()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'warehouse_id' => $warehouse->id,
                'code' => $request->string('code')->value(),
                'lot_number' => $request->input('lot_number'),
                'serial_number' => $request->input('serial_number'),
                'quantity_initial' => $request->input('quantity_initial'),
                'quantity_available' => 0,
                'unit_cost' => $request->input('unit_cost'),
                'status' => LotStatus::Available,
                'received_at' => $request->date('received_at') ?? now(),
                'notes' => $request->input('notes'),
            ]);

            $recordMovement->execute(
                product: $product,
                warehouse: $warehouse,
                movementType: MovementType::Adjustment,
                quantity: (string) $request->input('quantity_initial'),
                variant: $variant,
                lot: $lot,
                unitCost: (string) $request->input('unit_cost'),
                notes: 'Carga inicial de lote',
                user: $request->user(),
            );

            return $lot;
        });

        return redirect()
            ->route('inventory.lots.show', $lot)
            ->with('success', 'Lote creado correctamente.');
    }

    public function show(InventoryLot $lot): Response
    {
        $this->authorize('view', $lot);

        $lot->load(['product', 'variant', 'warehouse', 'purchaseDetail', 'movements', 'allocations']);

        return Inertia::render('Inventory/Lots/Show', [
            'lot' => (new InventoryLotResource($lot))->resolve(),
        ]);
    }

    public function edit(InventoryLot $lot): Response
    {
        $this->authorize('update', $lot);

        $lot->load(['product', 'variant', 'warehouse']);

        return Inertia::render('Inventory/Lots/Edit', [
            'lot' => (new InventoryLotResource($lot))->resolve(),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name', 'code']),
            'statuses' => LotStatus::options(),
        ]);
    }

    public function update(UpdateInventoryLotRequest $request, InventoryLot $lot): RedirectResponse
    {
        $this->authorize('update', $lot);

        $lot->update($request->safe()->only([
            'lot_number',
            'serial_number',
            'unit_cost',
            'status',
            'notes',
        ]));

        return redirect()
            ->route('inventory.lots.show', $lot)
            ->with('success', 'Lote actualizado correctamente.');
    }

    public function destroy(InventoryLot $lot): RedirectResponse
    {
        $this->authorize('delete', $lot);

        if ($lot->movements()->exists() || $lot->allocations()->exists() || $lot->childLots()->exists()) {
            return back()->with('error', 'No se puede eliminar el lote porque tiene registros relacionados.');
        }

        $lot->delete();

        return redirect()
            ->route('inventory.lots.index')
            ->with('success', 'Lote eliminado correctamente.');
    }
}
