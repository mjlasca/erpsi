<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Models\Unit;
use App\Modules\Catalog\Requests\ProductAttributeRequest;
use App\Modules\Catalog\Resources\ProductAttributeResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductAttributeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProductAttribute::class);

        $attributes = ProductAttribute::query()
            ->with(['product', 'variant', 'unit'])
            ->when($request->integer('product_id'), fn ($query, int $productId) => $query->where('product_id', $productId))
            ->when($request->string('search')->trim()->value(), fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Catalog/Attributes/Index', [
            'attributes' => $attributes->through(fn ($attribute): array => (new ProductAttributeResource($attribute))->resolve()),
            'filters' => $request->only('search', 'product_id'),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', ProductAttribute::class);

        return Inertia::render('Catalog/Attributes/Create', [
            ...$this->formOptions(),
            'selectedProductId' => $request->integer('product_id') ?: null,
        ]);
    }

    public function store(ProductAttributeRequest $request): RedirectResponse
    {
        $this->authorize('create', ProductAttribute::class);

        $attribute = ProductAttribute::query()->create($request->validated());

        return redirect()
            ->route('catalog.attributes.show', $attribute)
            ->with('success', 'Atributo creado correctamente.');
    }

    public function show(ProductAttribute $attribute): Response
    {
        $this->authorize('view', $attribute);

        $attribute->load(['product', 'variant', 'unit']);

        return Inertia::render('Catalog/Attributes/Show', [
            'attribute' => (new ProductAttributeResource($attribute))->resolve(),
        ]);
    }

    public function edit(ProductAttribute $attribute): Response
    {
        $this->authorize('update', $attribute);

        $attribute->load(['product', 'variant', 'unit']);

        return Inertia::render('Catalog/Attributes/Edit', [
            'attribute' => (new ProductAttributeResource($attribute))->resolve(),
            ...$this->formOptions(),
        ]);
    }

    public function update(ProductAttributeRequest $request, ProductAttribute $attribute): RedirectResponse
    {
        $this->authorize('update', $attribute);

        $attribute->update($request->validated());

        return redirect()
            ->route('catalog.attributes.show', $attribute)
            ->with('success', 'Atributo actualizado correctamente.');
    }

    public function destroy(ProductAttribute $attribute): RedirectResponse
    {
        $this->authorize('delete', $attribute);

        $attribute->delete();

        return redirect()
            ->route('catalog.attributes.index')
            ->with('success', 'Atributo eliminado correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'variants' => ProductVariant::query()->with('product')->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(['id', 'name', 'abbreviation']),
        ];
    }
}
