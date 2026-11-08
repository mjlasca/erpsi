<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Resources\ProductResource;
use App\Modules\Inventory\Actions\TransferInventory;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Requests\TransferInventoryRequest;
use App\Modules\Inventory\Resources\InventoryLotResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TransferInventoryController extends Controller
{
    public function create(): Response
    {
        $this->authorize('create', InventoryMovement::class);

        return Inertia::render('Inventory/Transfers/Create', [
            'products' => ProductResource::collection(
                Product::query()->with('variants')->active()->orderBy('name')->get(),
            )->resolve(),
            'warehouses' => Warehouse::query()->active()->orderBy('name')->get(['id', 'name', 'code']),
            'lots' => InventoryLotResource::collection(
                InventoryLot::query()->with(['product', 'variant', 'warehouse'])->available()->orderBy('code')->get(),
            )->resolve(),
        ]);
    }

    public function store(TransferInventoryRequest $request, TransferInventory $transferInventory): RedirectResponse
    {
        $this->authorize('create', InventoryMovement::class);

        $product = Product::query()->findOrFail($request->integer('product_id'));
        $variant = $request->filled('product_variant_id')
            ? ProductVariant::query()->findOrFail($request->integer('product_variant_id'))
            : null;
        $lot = $request->filled('inventory_lot_id')
            ? InventoryLot::query()->findOrFail($request->integer('inventory_lot_id'))
            : null;

        $transferInventory->execute(
            product: $product,
            fromWarehouse: Warehouse::query()->findOrFail($request->integer('from_warehouse_id')),
            toWarehouse: Warehouse::query()->findOrFail($request->integer('to_warehouse_id')),
            quantity: (string) $request->input('quantity'),
            variant: $variant,
            lot: $lot,
            notes: $request->input('notes'),
            user: $request->user(),
        );

        return redirect()
            ->route('inventory.movements.index')
            ->with('success', 'Transferencia registrada correctamente.');
    }
}
