<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Requests\StoreProductVariantRequest;
use App\Modules\Catalog\Requests\UpdateProductVariantRequest;
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

    public function store(StoreProductVariantRequest $request): RedirectResponse
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

    public function update(UpdateProductVariantRequest $request, ProductVariant $variant): RedirectResponse
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

        if (! $this->canDelete($variant)) {
            return back()->with('error', 'No se puede eliminar la variante porque tiene registros relacionados.');
        }

        $variant->delete();

        return redirect()
            ->route('catalog.variants.index')
            ->with('success', 'Variante eliminada correctamente.');
    }

    public function indexForProduct(Request $request, Product $product): Response
    {
        $request->merge(['product_id' => $product->id]);

        return $this->index($request);
    }

    public function createForProduct(Request $request, Product $product): Response
    {
        $request->merge(['product_id' => $product->id]);

        return $this->create($request);
    }

    public function storeForProduct(StoreProductVariantRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('create', ProductVariant::class);

        $variant = $product->variants()->create($request->validated());

        return redirect()
            ->route('catalog.products.variants.edit', [
                'product' => $product,
                'variant' => $variant,
            ])
            ->with('success', 'Variante creada correctamente.');
    }

    public function showForProduct(Product $product, ProductVariant $variant): Response
    {
        $this->ensureBelongsToProduct($product, $variant);

        return $this->show($variant);
    }

    public function editForProduct(Product $product, ProductVariant $variant): Response
    {
        $this->ensureBelongsToProduct($product, $variant);

        return $this->edit($variant);
    }

    public function updateForProduct(
        UpdateProductVariantRequest $request,
        Product $product,
        ProductVariant $variant,
    ): RedirectResponse {
        $this->ensureBelongsToProduct($product, $variant);

        $this->authorize('update', $variant);

        $variant->update($request->validated());

        return redirect()
            ->route('catalog.products.variants.edit', [
                'product' => $product,
                'variant' => $variant,
            ])
            ->with('success', 'Variante actualizada correctamente.');
    }

    public function destroyForProduct(Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->ensureBelongsToProduct($product, $variant);

        $this->authorize('delete', $variant);

        if (! $this->canDelete($variant)) {
            return back()->with('error', 'No se puede eliminar la variante porque tiene registros relacionados.');
        }

        $variant->delete();

        return redirect()
            ->route('catalog.products.variants.index', $product)
            ->with('success', 'Variante eliminada correctamente.');
    }

    protected function ensureBelongsToProduct(Product $product, ProductVariant $variant): void
    {
        abort_unless($variant->product_id === $product->id, 404);
    }

    protected function canDelete(ProductVariant $variant): bool
    {
        return ! $variant->stockItems()->exists()
            && ! $variant->lots()->exists()
            && ! $variant->movements()->exists()
            && ! $variant->purchaseDetails()->exists()
            && ! $variant->saleDetails()->exists();
    }
}
