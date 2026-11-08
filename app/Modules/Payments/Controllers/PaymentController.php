<?php

namespace App\Modules\Payments\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Actions\RecordPayment;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentType;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Requests\StorePaymentRequest;
use App\Modules\Payments\Requests\UpdatePaymentRequest;
use App\Modules\Payments\Resources\PaymentResource;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Resources\PurchaseResource;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Resources\SaleResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(protected RecordPayment $recordPayment) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->with(['customer', 'supplier', 'allocations'])
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('reference', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('payment_type'), fn ($query) => $query->where('payment_type', $request->string('payment_type')->value()))
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->string('payment_method')->value()))
            ->when($request->date('from'), fn ($query, $from) => $query->where('payment_date', '>=', $from->toDateString()))
            ->when($request->date('to'), fn ($query, $to) => $query->where('payment_date', '<=', $to->toDateString()))
            ->latest('payment_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Payments/Payments/Index', [
            'payments' => $payments->through(fn ($payment): array => (new PaymentResource($payment))->resolve()),
            'filters' => $request->only('search', 'payment_type', 'payment_method', 'from', 'to'),
            'paymentTypes' => PaymentType::options(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Payment::class);

        return Inertia::render('Payments/Payments/Create', [
            'customers' => Customer::query()->active()->orderBy('last_name')->orderBy('first_name')->get(),
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'paymentTypes' => PaymentType::options(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $payment = $this->recordPayment->execute($request->validated(), $request->user());

        return redirect()
            ->route('payments.payments.show', $payment)
            ->with('success', 'Pago registrado correctamente.');
    }

    public function show(Payment $payment): Response
    {
        $this->authorize('view', $payment);

        $payment->load([
            'customer',
            'supplier',
            'creator',
            'allocations.sale.customer',
            'allocations.purchase.supplier',
        ]);

        $openSales = $payment->isCustomerPayment()
            ? Sale::query()
                ->with('customer')
                ->where('customer_id', $payment->customer_id)
                ->whereIn('status', ['CONFIRMED', 'PARTIAL'])
                ->where('balance', '>', 0)
                ->orderBy('sale_date')
                ->get()
            : collect();

        $openPurchases = $payment->isSupplierPayment()
            ? Purchase::query()
                ->with('supplier')
                ->where('supplier_id', $payment->supplier_id)
                ->where('status', 'RECEIVED')
                ->where('balance', '>', 0)
                ->orderBy('purchase_date')
                ->get()
            : collect();

        return Inertia::render('Payments/Payments/Show', [
            'payment' => (new PaymentResource($payment))->resolve(),
            'openSales' => SaleResource::collection($openSales)->resolve(),
            'openPurchases' => PurchaseResource::collection($openPurchases)->resolve(),
        ]);
    }

    public function edit(Payment $payment): Response
    {
        $this->authorize('update', $payment);

        return Inertia::render('Payments/Payments/Edit', [
            'payment' => (new PaymentResource($payment->load(['customer', 'supplier', 'allocations'])))->resolve(),
            'customers' => Customer::query()->active()->orderBy('last_name')->orderBy('first_name')->get(),
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'paymentTypes' => PaymentType::options(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $payment);

        $validated = $request->validated();
        $type = PaymentType::from((string) $validated['payment_type']);

        $validated['customer_id'] = $type === PaymentType::CustomerPayment ? $validated['customer_id'] : null;
        $validated['supplier_id'] = $type === PaymentType::SupplierPayment ? $validated['supplier_id'] : null;

        $payment->update($validated);

        return redirect()
            ->route('payments.payments.show', $payment)
            ->with('success', 'Pago actualizado correctamente.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorize('delete', $payment);

        $payment->delete();

        return redirect()
            ->route('payments.payments.index')
            ->with('success', 'Pago eliminado correctamente.');
    }
}
