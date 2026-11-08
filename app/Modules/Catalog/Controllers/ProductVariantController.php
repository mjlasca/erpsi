<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Requests\ProductVariantRequest;
use App\Modules\Catalog\Resources\ProductVariantResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductVariantController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProductVariant::class);

        $variants = ProductVariant::query()
            ->with('product')
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('product_id'), fn ($query, int $productId) => $query->where('product_id', $productId))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Catalog/Variants/Index', [
            'variants' => $variants->through(fn ($variant): array => (new ProductVariantResource($variant))->resolve()),
            'filters' => $request->only('search', 'product_id'),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', ProductVariant::class);

        return Inertia::render('Catalog/Variants/Create', [
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'selectedProductId' => $request->integer('product_id') ?: null,
        ]);
    }

    public function store(ProductVariantRequest $request): RedirectResponse
    {
        $this->authorize('create', ProductVariant::class);

        $variant = ProductVariant::query()->create($request->validated());

        return redirect()
            ->route('catalog.variants.edit', $variant)
            ->with('success', 'Variante creada correctamente.');
    }

    public function show(ProductVariant $variant): Response
    {
        $this->authorize('view', $variant);

        $variant->load('product')->loadCount('attributes');

        return Inertia::render('Catalog/Variants/Show', [
            'variant' => (new ProductVariantResource($variant))->resolve(),
        ]);
    }

    public function edit(ProductVariant $variant): Response
    {
        $this->authorize('update', $variant);

        $variant->load('product');

        return Inertia::render('Catalog/Variants/Edit', [
            'variant' => (new ProductVariantResource($variant))->resolve(),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(ProductVariantRequest $request, ProductVariant $variant): RedirectResponse
    {
        $this->authorize('update', $variant);

        $variant->update($request->validated());

        return redirect()
            ->route('catalog.variants.edit', $variant)
            ->with('success', 'Variante actualizada correctamente.');
    }

    public function destroy(ProductVariant $variant): RedirectResponse
    {
        $this->authorize('delete', $variant);

        if ($variant->stockItems()->exists()
            || $variant->lots()->exists()
            || $variant->movements()->exists()
            || $variant->purchaseDetails()->exists()
            || $variant->saleDetails()->exists()) {
            return back()->with('error', 'No se puede eliminar la variante porque tiene registros relacionados.');
        }

        $variant->delete();

        return redirect()
            ->route('catalog.variants.index')
            ->with('success', 'Variante eliminada correctamente.');
    }
}
