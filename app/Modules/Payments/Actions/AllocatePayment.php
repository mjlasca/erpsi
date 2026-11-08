<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Sales\Models\Sale;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AllocatePayment
{
    public function __construct(protected RecalculateDocumentBalance $recalculateBalance) {}

    public function execute(
        Payment $payment,
        Sale|Purchase $document,
        string $amount,
    ): PaymentAllocation {
        $amount = Decimal::round2($amount);

        if (Decimal::compare($amount, '0', 2) <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'El monto a aplicar debe ser mayor que cero.',
            ]);
        }

        return DB::transaction(function () use ($payment, $document, $amount): PaymentAllocation {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $document = $document::query()->lockForUpdate()->findOrFail($document->id);

            if ($document instanceof Sale) {
                if (! $payment->isCustomerPayment()) {
                    throw ValidationException::withMessages([
                        'payment_id' => 'El pago debe ser un cobro a cliente para aplicarse a una venta.',
                    ]);
                }

                if ($document->isDraft() || $document->isCancelled()) {
                    throw ValidationException::withMessages([
                        'sale_id' => 'Solo se pueden abonar ventas confirmadas.',
                    ]);
                }

                if ($document->customer_id !== $payment->customer_id) {
                    throw ValidationException::withMessages([
                        'sale_id' => 'La venta no pertenece al cliente del pago.',
                    ]);
                }
            }

            if ($document instanceof Purchase) {
                if (! $payment->isSupplierPayment()) {
                    throw ValidationException::withMessages([
                        'payment_id' => 'El pago debe ser un pago a proveedor para aplicarse a una compra.',
                    ]);
                }

                if (! $document->isReceived()) {
                    throw ValidationException::withMessages([
                        'purchase_id' => 'Solo se pueden abonar compras recibidas.',
                    ]);
                }

                if ($document->supplier_id !== $payment->supplier_id) {
                    throw ValidationException::withMessages([
                        'purchase_id' => 'La compra no pertenece al proveedor del pago.',
                    ]);
                }
            }

            $allocated = Decimal::sum($payment->allocations()->pluck('amount'), 2);
            $paymentBalance = Decimal::maxZero(Decimal::sub((string) $payment->amount, $allocated, 2));

            if (Decimal::compare($amount, $paymentBalance, 2) > 0) {
                throw ValidationException::withMessages([
                    'amount' => 'El monto excede el saldo disponible del pago.',
                ]);
            }

            if (Decimal::compare($amount, (string) $document->balance, 2) > 0) {
                throw ValidationException::withMessages([
                    'amount' => 'El monto excede el saldo del documento.',
                ]);
            }

            $allocation = PaymentAllocation::query()->create([
                'payment_id' => $payment->id,
                'sale_id' => $document instanceof Sale ? $document->id : null,
                'purchase_id' => $document instanceof Purchase ? $document->id : null,
                'amount' => $amount,
            ]);

            $this->recalculateBalance->execute($document);

            return $allocation;
        });
    }
}
