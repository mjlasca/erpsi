<?php

namespace App\Modules\Purchasing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Purchasing\Actions\CancelPurchaseReturn;
use App\Modules\Purchasing\Actions\ConfirmPurchaseReturn;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Purchasing\Models\PurchaseDetail;
use App\Modules\Purchasing\Models\PurchaseReturn;
use App\Modules\Purchasing\Models\PurchaseReturnDetail;
use App\Modules\Purchasing\Requests\StorePurchaseReturnRequest;
use App\Modules\Purchasing\Resources\PurchaseReturnResource;
use App\Modules\Sales\Enums\ReturnStatus;
use App\Support\Decimal;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseReturnController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PurchaseReturn::class);

        $returns = PurchaseReturn::query()
            ->with(['purchase.supplier'])
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('return_number', 'like', "%{$search}%")
                        ->orWhereHas('purchase', fn ($query) => $query->where('purchase_number', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->latest('return_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Purchasing/Returns/Index', [
            'returns' => $returns->through(fn ($return): array => (new PurchaseReturnResource($return))->resolve()),
            'filters' => $request->only('search', 'status'),
            'statuses' => ReturnStatus::options(),
        ]);
    }

    public function create(Purchase $purchase): Response
    {
        $this->authorize('create', PurchaseReturn::class);
        abort_unless($purchase->isReceived(), 403);

        $purchase->load(['supplier', 'details.product', 'details.variant', 'details.lots']);

        $details = $purchase->details->map(function (PurchaseDetail $detail): array {
            $returned = PurchaseReturnDetail::query()
                ->where('purchase_detail_id', $detail->id)
                ->whereHas('purchaseReturn', fn ($query) => $query->where('status', ReturnStatus::Confirmed))
                ->sum('quantity');

            return [
                'id' => $detail->id,
                'quantity' => $detail->quantity,
                'unit_price' => $detail->unit_price,
                'returned_quantity' => (string) $returned,
                'pending_quantity' => Decimal::maxZero(Decimal::sub((string) $detail->quantity, (string) $returned, 3), 3),
                'product' => $detail->product?->only(['id', 'name', 'sku', 'track_lots']),
                'variant' => $detail->variant?->only(['id', 'name', 'sku']),
                'lots' => $detail->lots->map(fn ($lot): array => [
                    'id' => $lot->id,
                    'code' => $lot->code,
                    'quantity_available' => $lot->quantity_available,
                    'status' => $lot->status->value,
                ])->all(),
            ];
        })->values()->all();

        return Inertia::render('Purchasing/Returns/Create', [
            'purchase' => [
                'id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'supplier' => $purchase->supplier?->only(['id', 'name']),
            ],
            'details' => $details,
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function store(StorePurchaseReturnRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->authorize('create', PurchaseReturn::class);
        abort_unless($purchase->isReceived(), 403);

        $validated = $request->validated();
        $purchase->load('details');

        $return = DB::transaction(function () use ($validated, $purchase, $request): PurchaseReturn {
            $return = PurchaseReturn::query()->create([
                'purchase_id' => $purchase->id,
                'return_number' => (app(TenantContext::class)->tenant() === null
                    ? 'RCOMP'
                    : app(TenantContext::class)->tenant()->purchase_return_prefix).'-'.Str::upper((string) Str::ulid()),
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
                $original = $purchase->details->firstWhere('id', (int) $row['purchase_detail_id']);

                if ($original === null) {
                    throw ValidationException::withMessages([
                        'details' => 'Una de las líneas no pertenece a la compra seleccionada.',
                    ]);
                }

                $calculated = $this->calculateLine((string) $row['quantity'], $original);

                PurchaseReturnDetail::query()->create([
                    'purchase_return_id' => $return->id,
                    'purchase_detail_id' => $original->id,
                    'product_id' => $original->product_id,
                    'product_variant_id' => $original->product_variant_id,
                    'inventory_lot_id' => $row['inventory_lot_id'] ?? null,
                    'quantity' => $row['quantity'],
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
            ->route('purchasing.returns.show', $return)
            ->with('success', __('messages.return_created'));
    }

    public function show(PurchaseReturn $purchaseReturn): Response
    {
        $this->authorize('view', $purchaseReturn);

        $purchaseReturn->load(['purchase.supplier', 'details.product', 'details.variant', 'details.lot']);

        return Inertia::render('Purchasing/Returns/Show', [
            'return' => (new PurchaseReturnResource($purchaseReturn))->resolve(),
        ]);
    }

    public function confirm(Request $request, PurchaseReturn $purchaseReturn, ConfirmPurchaseReturn $confirm): RedirectResponse
    {
        $this->authorize('confirm', $purchaseReturn);
        $confirm->execute($purchaseReturn, $request->user());

        return back()->with('success', __('messages.return_confirmed'));
    }

    public function cancel(Request $request, PurchaseReturn $purchaseReturn, CancelPurchaseReturn $cancel): RedirectResponse
    {
        $this->authorize('cancel', $purchaseReturn);
        $cancel->execute($purchaseReturn, $request->user());

        return back()->with('success', __('messages.return_cancelled'));
    }

    public function destroy(PurchaseReturn $purchaseReturn): RedirectResponse
    {
        $this->authorize('delete', $purchaseReturn);
        abort_unless($purchaseReturn->isDraft(), 403);

        $purchaseReturn->delete();

        return redirect()
            ->route('purchasing.returns.index')
            ->with('success', __('messages.return_deleted'));
    }

    /** @return array<string, string> */
    protected function calculateLine(string $quantity, PurchaseDetail $original): array
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
