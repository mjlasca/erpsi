<?php

namespace App\Modules\Sales\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Sales\Actions\CancelSaleReturn;
use App\Modules\Sales\Actions\ConfirmSaleReturn;
use App\Modules\Sales\Enums\ReturnDisposition;
use App\Modules\Sales\Enums\ReturnStatus;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleDetail;
use App\Modules\Sales\Models\SaleReturn;
use App\Modules\Sales\Models\SaleReturnDetail;
use App\Modules\Sales\Requests\StoreSaleReturnRequest;
use App\Modules\Sales\Resources\SaleReturnResource;
use App\Support\Decimal;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SaleReturnController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SaleReturn::class);

        $returns = SaleReturn::query()
            ->with(['sale.customer'])
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('return_number', 'like', "%{$search}%")
                        ->orWhereHas('sale', fn ($query) => $query->where('invoice_number', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->latest('return_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Sales/Returns/Index', [
            'returns' => $returns->through(fn ($return): array => (new SaleReturnResource($return))->resolve()),
            'filters' => $request->only('search', 'status'),
            'statuses' => ReturnStatus::options(),
        ]);
    }

    public function create(Sale $sale): Response
    {
        $this->authorize('create', SaleReturn::class);
        abort_unless($sale->isConfirmed(), 403);

        $sale->load([
            'customer',
            'details.product',
            'details.variant',
            'details.lotAllocations.lot',
        ]);

        $details = $sale->details->map(function (SaleDetail $detail): array {
            $returned = SaleReturnDetail::query()
                ->where('sale_detail_id', $detail->id)
                ->whereHas('saleReturn', fn ($query) => $query->where('status', ReturnStatus::Confirmed))
                ->sum('quantity');

            return [
                'id' => $detail->id,
                'quantity' => $detail->quantity,
                'unit_price' => $detail->unit_price,
                'returned_quantity' => (string) $returned,
                'pending_quantity' => Decimal::maxZero(Decimal::sub((string) $detail->quantity, (string) $returned, 3), 3),
                'product' => $detail->product?->only(['id', 'name', 'sku', 'track_lots']),
                'variant' => $detail->variant?->only(['id', 'name', 'sku']),
                'lot_allocations' => $detail->lotAllocations->map(fn ($allocation): array => [
                    'id' => $allocation->id,
                    'inventory_lot_id' => $allocation->inventory_lot_id,
                    'quantity' => $allocation->quantity,
                    'code' => $allocation->lot?->code,
                ])->all(),
            ];
        })->values()->all();

        return Inertia::render('Sales/Returns/Create', [
            'sale' => [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'customer' => $sale->customer?->only(['id', 'full_name']),
            ],
            'details' => $details,
            'dispositions' => ReturnDisposition::options(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function store(StoreSaleReturnRequest $request, Sale $sale): RedirectResponse
    {
        $this->authorize('create', SaleReturn::class);
        abort_unless($sale->isConfirmed(), 403);

        $validated = $request->validated();
        $sale->load('details');

        $return = DB::transaction(function () use ($validated, $sale, $request): SaleReturn {
            $return = SaleReturn::query()->create([
                'sale_id' => $sale->id,
                'return_number' => (app(TenantContext::class)->tenant() === null
                    ? 'RDEV'
                    : app(TenantContext::class)->tenant()->sale_return_prefix).'-'.Str::upper((string) Str::ulid()),
                'return_date' => $validated['return_date'],
                'status' => ReturnStatus::Draft,
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'refunded_amount' => Decimal::round2((string) ($validated['refund_amount'] ?? '0')),
                'refund_method' => $validated['refund_method'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            $subtotal = '0.00';
            $taxAmount = '0.00';
            $total = '0.00';

            foreach ($validated['details'] as $row) {
                $original = $sale->details->firstWhere('id', (int) $row['sale_detail_id']);

                if ($original === null) {
                    throw ValidationException::withMessages([
                        'details' => 'Una de las líneas no pertenece a la venta seleccionada.',
                    ]);
                }

                $calculated = $this->calculateLine(
                    (string) $row['quantity'],
                    $original,
                );

                SaleReturnDetail::query()->create([
                    'sale_return_id' => $return->id,
                    'sale_detail_id' => $original->id,
                    'product_id' => $original->product_id,
                    'product_variant_id' => $original->product_variant_id,
                    'inventory_lot_id' => $row['inventory_lot_id'] ?? null,
                    'quantity' => $row['quantity'],
                    'disposition' => $row['disposition'],
                    ...$calculated,
                ]);

                $subtotal = Decimal::add($subtotal, $calculated['subtotal'], 2);
                $taxAmount = Decimal::add($taxAmount, $calculated['tax_amount'], 2);
                $total = Decimal::add($total, $calculated['total'], 2);
            }

            $return->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
            ]);

            return $return;
        });

        return redirect()
            ->route('sales.returns.show', $return)
            ->with('success', __('messages.return_created'));
    }

    public function show(SaleReturn $saleReturn): Response
    {
        $this->authorize('view', $saleReturn);

        $saleReturn->load(['sale.customer', 'details.product', 'details.variant', 'details.lot']);

        return Inertia::render('Sales/Returns/Show', [
            'return' => (new SaleReturnResource($saleReturn))->resolve(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function confirm(Request $request, SaleReturn $saleReturn, ConfirmSaleReturn $confirm): RedirectResponse
    {
        $this->authorize('confirm', $saleReturn);

        $confirm->execute($saleReturn, $request->user());

        return back()->with('success', __('messages.return_confirmed'));
    }

    public function cancel(Request $request, SaleReturn $saleReturn, CancelSaleReturn $cancel): RedirectResponse
    {
        $this->authorize('cancel', $saleReturn);

        $cancel->execute($saleReturn, $request->user());

        return back()->with('success', __('messages.return_cancelled'));
    }

    public function destroy(SaleReturn $saleReturn): RedirectResponse
    {
        $this->authorize('delete', $saleReturn);
        abort_unless($saleReturn->isDraft(), 403);

        $saleReturn->delete();

        return redirect()
            ->route('sales.returns.index')
            ->with('success', __('messages.return_deleted'));
    }

    /**
     * @return array<string, string>
     */
    protected function calculateLine(string $quantity, SaleDetail $original): array
    {
        $ratio = Decimal::compare((string) $original->quantity, '0', 3) === 0
            ? '0'
            : Decimal::divide($quantity, (string) $original->quantity, 8);

        $gross = Decimal::multiply($quantity, (string) $original->unit_price, 4);
        $discount = Decimal::round2(Decimal::multiply((string) $original->discount, $ratio, 4));
        $lineSubtotal = Decimal::maxZero(Decimal::sub($gross, $discount, 4), 4);
        $tax = Decimal::round2(Decimal::multiply(
            $lineSubtotal,
            Decimal::divide((string) $original->tax_rate, '100', 8),
            4,
        ));

        return [
            'unit_price' => (string) $original->unit_price,
            'discount' => $discount,
            'tax_rate' => (string) $original->tax_rate,
            'subtotal' => Decimal::round2($lineSubtotal),
            'tax_amount' => $tax,
            'total' => Decimal::add(Decimal::round2($lineSubtotal), $tax, 2),
        ];
    }
}
