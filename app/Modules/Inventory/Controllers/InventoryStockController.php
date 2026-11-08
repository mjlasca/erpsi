<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Resources\InventoryStockResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryStockController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', InventoryStock::class);

        $stock = InventoryStock::query()
            ->with(['product', 'variant', 'warehouse'])
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->whereHas('product', function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('warehouse_id'), fn ($query, int $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($request->boolean('low_stock'), fn ($query) => $query->lowStock())
            ->orderBy('warehouse_id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('inventory/stock/index', [
            'stock' => InventoryStockResource::collection($stock)->resolve(),
            'filters' => $request->only('search', 'warehouse_id', 'low_stock'),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function show(InventoryStock $stock): Response
    {
        $this->authorize('view', $stock);

        $stock->load(['product', 'variant', 'warehouse']);

        return Inertia::render('inventory/stock/show', [
            'stock' => (new InventoryStockResource($stock))->resolve(),
        ]);
    }
}
