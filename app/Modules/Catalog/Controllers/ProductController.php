<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Enums\ProductType;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductCategory;
use App\Modules\Catalog\Models\Unit;
use App\Modules\Catalog\Requests\ProductRequest;
use App\Modules\Catalog\Resources\ProductResource;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
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

        return Inertia::render('catalog/products/index', [
            'products' => ProductResource::collection($products)->resolve(),
            'filters' => $request->only('search', 'category_id', 'is_active'),
            'categories' => ProductCategory::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('catalog/products/create', $this->formOptions());
    }

    public function store(ProductRequest $request): RedirectResponse
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

        return Inertia::render('catalog/products/show', [
            'product' => (new ProductResource($product))->resolve(),
        ]);
    }

    public function edit(Product $product): Response
    {
        $this->authorize('update', $product);

        $product->load(['category', 'unit']);

        return Inertia::render('catalog/products/edit', [
            'product' => (new ProductResource($product))->resolve(),
            ...$this->formOptions(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
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

        try {
            $product->delete();
        } catch (QueryException) {
            return back()->with('error', 'No se puede eliminar el producto porque tiene registros relacionados.');
        }

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
