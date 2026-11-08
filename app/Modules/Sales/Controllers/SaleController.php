<?php

namespace App\Modules\Sales\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Models\TaxRate;
use App\Modules\Administration\Resources\TaxRateResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Resources\ProductResource;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Sales\Actions\CancelSale;
use App\Modules\Sales\Actions\ConfirmSale;
use App\Modules\Sales\Actions\RecalculateSaleTotals;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleDetail;
use App\Modules\Sales\Requests\ConfirmSaleRequest;
use App\Modules\Sales\Requests\SaleRequest;
use App\Modules\Sales\Resources\SaleResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function __construct(
        protected RecalculateSaleTotals $recalculateTotals,
        protected ConfirmSale $confirmSale,
        protected CancelSale $cancelSale,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Sale::class);

        $sales = Sale::query()
            ->with(['customer', 'warehouse'])
            ->withCount('details')
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($query) use ($search): void {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->when($request->integer('customer_id'), fn ($query, int $customerId) => $query->where('customer_id', $customerId))
            ->latest('sale_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('sales/sales/index', [
            'sales' => SaleResource::collection($sales)->resolve(),
            'filters' => $request->only('search', 'status', 'customer_id'),
            'customers' => Customer::query()->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'company_name']),
            'statuses' => SaleStatus::options(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Sale::class);

        return Inertia::render('sales/sales/create', $this->formOptions());
    }

    public function store(SaleRequest $request): RedirectResponse
    {
        $this->authorize('create', Sale::class);

        $validated = $request->validated();
        $details = $validated['details'];
        unset($validated['details']);

        $sale = DB::transaction(function () use ($validated, $details, $request): Sale {
            $sale = Sale::query()->create([
                ...$validated,
                'invoice_number' => 'INV-'.Str::upper((string) Str::ulid()),
                'status' => SaleStatus::Draft,
                'created_by' => $request->user()?->id,
            ]);

            $this->syncDetails($sale, $details);
            $this->recalculateTotals->execute($sale);

            return $sale;
        });

        return redirect()
            ->route('sales.sales.show', $sale)
            ->with('success', 'Venta creada correctamente.');
    }

    public function show(Sale $sale): Response
    {
        $this->authorize('view', $sale);

        $sale->load([
            'customer',
            'warehouse',
            'creator',
            'details.product',
            'details.variant',
            'details.taxRate',
            'details.lotAllocations.lot',
            'allocations.payment',
        ]);

        return Inertia::render('sales/sales/show', [
            'sale' => (new SaleResource($sale))->resolve(),
        ]);
    }

    public function edit(Sale $sale): Response
    {
        $this->authorize('update', $sale);

        $sale->load(['customer', 'warehouse', 'details.product', 'details.variant', 'details.taxRate']);

        return Inertia::render('sales/sales/edit', [
            'sale' => (new SaleResource($sale))->resolve(),
            ...$this->formOptions(),
        ]);
    }

    public function update(SaleRequest $request, Sale $sale): RedirectResponse
    {
        $this->authorize('update', $sale);

        $validated = $request->validated();
        $details = $validated['details'];
        unset($validated['details']);

        DB::transaction(function () use ($sale, $validated, $details): void {
            $sale->update($validated);
            $this->syncDetails($sale, $details);
            $this->recalculateTotals->execute($sale);
        });

        return redirect()
            ->route('sales.sales.show', $sale)
            ->with('success', 'Venta actualizada correctamente.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $this->authorize('delete', $sale);

        $sale->delete();

        return redirect()
            ->route('sales.sales.index')
            ->with('success', 'Venta eliminada correctamente.');
    }

    public function confirm(ConfirmSaleRequest $request, Sale $sale): RedirectResponse
    {
        $this->authorize('confirm', $sale);

        $this->confirmSale->execute(
            $sale,
            $request->validated()['lot_allocations'] ?? [],
            $request->user(),
        );

        return redirect()
            ->route('sales.sales.show', $sale)
            ->with('success', 'Venta confirmada e inventario actualizado.');
    }

    public function cancel(Request $request, Sale $sale): RedirectResponse
    {
        $this->authorize('cancel', $sale);

        $this->cancelSale->execute($sale, $request->user());

        return redirect()
            ->route('sales.sales.show', $sale)
            ->with('success', 'Venta cancelada correctamente.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncDetails(Sale $sale, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            $detailId = $row['id'] ?? null;

            if ($detailId !== null) {
                $detail = $sale->details()->whereKey($detailId)->first();

                if ($detail === null) {
                    throw ValidationException::withMessages([
                        'details' => 'Una de las líneas no pertenece a esta venta.',
                    ]);
                }
            } else {
                $detail = new SaleDetail;
                $detail->sale_id = $sale->id;
            }

            $taxRate = isset($row['tax_rate_id'])
                ? TaxRate::query()->find($row['tax_rate_id'])
                : null;

            $detail->fill([
                'sale_id' => $sale->id,
                'product_id' => $row['product_id'],
                'product_variant_id' => $row['product_variant_id'] ?? null,
                'quantity' => $row['quantity'],
                'unit_price' => $row['unit_price'],
                'discount' => $row['discount'] ?? 0,
                'tax_rate_id' => $row['tax_rate_id'] ?? null,
                'tax_rate' => $taxRate?->rate ?? 0,
            ]);

            $detail->save();
            $keptIds[] = $detail->id;
        }

        $sale->details()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'customers' => Customer::query()->active()->orderBy('last_name')->orderBy('first_name')->get(),
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
