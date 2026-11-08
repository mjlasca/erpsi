<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Enums\ProductType;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductCategory;
use App\Modules\Catalog\Models\Unit;
use App\Modules\Catalog\Requests\StoreProductRequest;
use App\Modules\Catalog\Requests\UpdateProductRequest;
use App\Modules\Catalog\Resources\ProductResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function search(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->with(['unit', 'variants'])
            ->active()
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return ProductResource::collection($products);
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->with(['category', 'unit'])
            ->withCount('variants')
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('category_id'), fn ($query, int $categoryId) => $query->where('category_id', $categoryId))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Catalog/Products/Index', [
            'products' => $products->through(fn ($product): array => (new ProductResource($product))->resolve()),
            'filters' => $request->only('search', 'category_id', 'is_active'),
            'categories' => ProductCategory::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Catalog/Products/Create', $this->formOptions());
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $product = Product::query()->create($request->validated());

        return redirect()
            ->route('catalog.products.show', $product)
            ->with('success', 'Producto creado correctamente.');
    }

    public function show(Product $product): Response
    {
        $this->authorize('view', $product);

        $product->load(['category', 'unit', 'variants', 'attributes.unit']);

        return Inertia::render('Catalog/Products/Show', [
            'product' => (new ProductResource($product))->resolve(),
        ]);
    }

    public function edit(Product $product): Response
    {
        $this->authorize('update', $product);

        $product->load(['category', 'unit']);

        return Inertia::render('Catalog/Products/Edit', [
            'product' => (new ProductResource($product))->resolve(),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        return redirect()
            ->route('catalog.products.show', $product)
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        if ($product->stockItems()->exists()
            || $product->lots()->exists()
            || $product->movements()->exists()
            || $product->purchaseDetails()->exists()
            || $product->saleDetails()->exists()) {
            return back()->with('error', 'No se puede eliminar el producto porque tiene registros relacionados.');
        }

        $product->delete();

        return redirect()
            ->route('catalog.products.index')
            ->with('success', 'Producto eliminado correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'categories' => ProductCategory::query()->orderBy('name')->get(['id', 'name']),
            'units' => Unit::query()->active()->orderBy('name')->get(['id', 'name', 'abbreviation']),
            'productTypes' => ProductType::options(),
        ];
    }
}
