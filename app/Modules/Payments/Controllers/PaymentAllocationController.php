<?php

namespace App\Modules\Payments\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Actions\AllocatePayment;
use App\Modules\Payments\Actions\RemovePaymentAllocation;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Payments\Requests\PaymentAllocationRequest;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Sales\Models\Sale;
use Illuminate\Http\RedirectResponse;

class PaymentAllocationController extends Controller
{
    public function store(
        PaymentAllocationRequest $request,
        Payment $payment,
        AllocatePayment $allocatePayment,
    ): RedirectResponse {
        $this->authorize('allocate', $payment);

        $document = $request->filled('sale_id')
            ? Sale::query()->findOrFail($request->integer('sale_id'))
            : Purchase::query()->findOrFail($request->integer('purchase_id'));

        $allocatePayment->execute($payment, $document, (string) $request->input('amount'));

        return redirect()
            ->route('payments.payments.show', $payment)
            ->with('success', 'Pago aplicado correctamente.');
    }

    public function destroy(
        Payment $payment,
        PaymentAllocation $allocation,
        RemovePaymentAllocation $removeAllocation,
    ): RedirectResponse {
        $this->authorize('delete', $allocation);

        abort_unless($allocation->payment_id === $payment->id, 404);

        $removeAllocation->execute($allocation);

        return redirect()
            ->route('payments.payments.show', $payment)
            ->with('success', 'Aplicación de pago eliminada correctamente.');
    }
}
