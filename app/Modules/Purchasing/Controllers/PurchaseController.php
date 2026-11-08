<?php

namespace App\Modules\Purchasing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Models\TaxRate;
use App\Modules\Administration\Resources\TaxRateResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Resources\ProductResource;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Purchasing\Actions\CancelPurchase;
use App\Modules\Purchasing\Actions\RecalculatePurchaseTotals;
use App\Modules\Purchasing\Actions\ReceivePurchase;
use App\Modules\Purchasing\Enums\PurchaseStatus;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Purchasing\Models\PurchaseDetail;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Requests\PurchaseRequest;
use App\Modules\Purchasing\Resources\PurchaseResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function __construct(
        protected RecalculatePurchaseTotals $recalculateTotals,
        protected ReceivePurchase $receivePurchase,
        protected CancelPurchase $cancelPurchase,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Purchase::class);

        $purchases = Purchase::query()
            ->with(['supplier', 'warehouse'])
            ->withCount('details')
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('purchase_number', 'like', "%{$search}%")
                        ->orWhere('supplier_invoice', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->when($request->integer('supplier_id'), fn ($query, int $supplierId) => $query->where('supplier_id', $supplierId))
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Purchasing/Purchases/Index', [
            'purchases' => $purchases->through(fn ($purchase): array => (new PurchaseResource($purchase))->resolve()),
            'filters' => $request->only('search', 'status', 'supplier_id'),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => PurchaseStatus::options(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Purchase::class);

        return Inertia::render('Purchasing/Purchases/Create', $this->formOptions());
    }

    public function store(PurchaseRequest $request): RedirectResponse
    {
        $this->authorize('create', Purchase::class);

        $validated = $request->validated();
        $details = $validated['details'];
        unset($validated['details']);

        $purchase = DB::transaction(function () use ($validated, $details, $request): Purchase {
            $purchase = Purchase::query()->create([
                ...$validated,
                'purchase_number' => 'PUR-'.Str::upper((string) Str::ulid()),
                'status' => PurchaseStatus::Draft,
                'created_by' => $request->user()?->id,
            ]);

            $this->syncDetails($purchase, $details);
            $this->recalculateTotals->execute($purchase);

            return $purchase;
        });

        return redirect()
            ->route('purchasing.purchases.show', $purchase)
            ->with('success', 'Compra creada correctamente.');
    }

    public function show(Purchase $purchase): Response
    {
        $this->authorize('view', $purchase);

        $purchase->load([
            'supplier',
            'warehouse',
            'creator',
            'details.product',
            'details.variant',
            'details.taxRate',
            'allocations.payment',
        ]);

        return Inertia::render('Purchasing/Purchases/Show', [
            'purchase' => (new PurchaseResource($purchase))->resolve(),
        ]);
    }

    public function edit(Purchase $purchase): Response
    {
        $this->authorize('update', $purchase);

        $purchase->load(['supplier', 'warehouse', 'details.product', 'details.variant', 'details.taxRate']);

        return Inertia::render('Purchasing/Purchases/Edit', [
            'purchase' => (new PurchaseResource($purchase))->resolve(),
            ...$this->formOptions(),
        ]);
    }

    public function update(PurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->authorize('update', $purchase);

        $validated = $request->validated();
        $details = $validated['details'];
        unset($validated['details']);

        DB::transaction(function () use ($purchase, $validated, $details): void {
            $purchase->update($validated);
            $this->syncDetails($purchase, $details);
            $this->recalculateTotals->execute($purchase);
        });

        return redirect()
            ->route('purchasing.purchases.show', $purchase)
            ->with('success', 'Compra actualizada correctamente.');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        $this->authorize('delete', $purchase);

        $purchase->delete();

        return redirect()
            ->route('purchasing.purchases.index')
            ->with('success', 'Compra eliminada correctamente.');
    }

    public function receive(Request $request, Purchase $purchase): RedirectResponse
    {
        $this->authorize('receive', $purchase);

        $this->receivePurchase->execute($purchase, $request->user());

        return redirect()
            ->route('purchasing.purchases.show', $purchase)
            ->with('success', 'Compra recibida e inventario actualizado.');
    }

    public function cancel(Request $request, Purchase $purchase): RedirectResponse
    {
        $this->authorize('cancel', $purchase);

        $this->cancelPurchase->execute($purchase, $request->user());

        return redirect()
            ->route('purchasing.purchases.show', $purchase)
            ->with('success', 'Compra cancelada correctamente.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncDetails(Purchase $purchase, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            $detailId = $row['id'] ?? null;

            if ($detailId !== null) {
                $detail = $purchase->details()->whereKey($detailId)->first();

                if ($detail === null) {
                    throw ValidationException::withMessages([
                        'details' => 'Una de las líneas no pertenece a esta compra.',
                    ]);
                }
            } else {
                $detail = new PurchaseDetail;
                $detail->purchase_id = $purchase->id;
            }

            $taxRate = isset($row['tax_rate_id'])
                ? TaxRate::query()->whereKey((int) $row['tax_rate_id'])->value('rate')
                : null;

            $detail->fill([
                'purchase_id' => $purchase->id,
                'product_id' => $row['product_id'],
                'product_variant_id' => $row['product_variant_id'] ?? null,
                'quantity' => $row['quantity'],
                'unit_price' => $row['unit_price'],
                'discount' => $row['discount'] ?? 0,
                'tax_rate_id' => $row['tax_rate_id'] ?? null,
                'tax_rate' => $taxRate ?? 0,
            ]);

            $detail->save();
            $keptIds[] = $detail->id;
        }

        $purchase->details()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->active()->orderBy('name')->get(['id', 'name', 'code']),
            'products' => ProductResource::collection(
                Product::query()->with(['unit', 'variants'])->active()->orderBy('name')->get(),
            )->resolve(),
            'taxRates' => TaxRateResource::collection(
                TaxRate::query()->active()->orderBy('name')->get(),
            )->resolve(),
        ];
    }
}
