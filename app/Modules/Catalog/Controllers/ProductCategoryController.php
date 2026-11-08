<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\ProductCategory;
use App\Modules\Catalog\Requests\StoreProductCategoryRequest;
use App\Modules\Catalog\Requests\UpdateProductCategoryRequest;
use App\Modules\Catalog\Resources\ProductCategoryResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProductCategory::class);

        $categories = ProductCategory::query()
            ->with('parent')
            ->withCount(['products', 'children'])
            ->when($request->string('search')->trim()->value(), fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Catalog/Categories/Index', [
            'categories' => $categories->through(fn ($category): array => (new ProductCategoryResource($category))->resolve()),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ProductCategory::class);

        return Inertia::render('Catalog/Categories/Create', $this->formOptions());
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', ProductCategory::class);

        $category = ProductCategory::query()->create($request->validated());

        return redirect()
            ->route('catalog.categories.show', $category)
            ->with('success', 'Categoría creada correctamente.');
    }

    public function show(ProductCategory $category): Response
    {
        $this->authorize('view', $category);

        $category->load(['parent', 'children'])->loadCount('products');

        return Inertia::render('Catalog/Categories/Show', [
            'category' => (new ProductCategoryResource($category))->resolve(),
        ]);
    }

    public function edit(ProductCategory $category): Response
    {
        $this->authorize('update', $category);

        $category->load('parent');

        return Inertia::render('Catalog/Categories/Edit', [
            'category' => (new ProductCategoryResource($category))->resolve(),
            ...$this->formOptions($category),
        ]);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $category->update($request->validated());

        return redirect()
            ->route('catalog.categories.show', $category)
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if ($category->children()->exists() || $category->products()->exists()) {
            return back()->with('error', 'No se puede eliminar la categoría porque tiene registros relacionados.');
        }

        $category->delete();

        return redirect()
            ->route('catalog.categories.index')
            ->with('success', 'Categoría eliminada correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(?ProductCategory $category = null): array
    {
        return [
            'categories' => ProductCategory::query()
                ->when($category, fn ($query) => $query->where('id', '!=', $category->id))
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}
