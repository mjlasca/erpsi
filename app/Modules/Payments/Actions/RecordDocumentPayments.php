<?php

namespace App\Modules\Payments\Actions;

use App\Models\User;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentType;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Sales\Models\Sale;
use App\Support\Decimal;
use Illuminate\Validation\ValidationException;

class RecordDocumentPayments
{
    public function __construct(
        protected RecordPayment $recordPayment,
        protected AllocatePayment $allocatePayment,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $payments
     */
    public function execute(
        Sale|Purchase $document,
        array $payments,
        ?User $user = null,
    ): void {
        $total = Decimal::round2((string) $document->total);
        $amounts = array_map(
            fn (array $payment): string => (string) ($payment['amount'] ?? '0'),
            $payments,
        );
        $sum = Decimal::sum($amounts, 2);

        if (Decimal::compare($sum, $total, 2) !== 0) {
            throw ValidationException::withMessages([
                'payments' => 'El total de los pagos debe coincidir con el total del documento.',
            ]);
        }

        $paymentType = $document instanceof Sale
            ? PaymentType::CustomerPayment
            : PaymentType::SupplierPayment;

        foreach ($payments as $payment) {
            $recorded = $this->recordPayment->execute([
                'payment_type' => $paymentType,
                'customer_id' => $document instanceof Sale ? $document->customer_id : null,
                'supplier_id' => $document instanceof Purchase ? $document->supplier_id : null,
                'payment_date' => now()->toDateString(),
                'amount' => $payment['amount'],
                'payment_method' => $payment['payment_method'] instanceof PaymentMethod
                    ? $payment['payment_method']
                    : PaymentMethod::from((string) $payment['payment_method']),
                'reference' => $payment['reference'] ?? null,
                'notes' => $payment['notes'] ?? null,
            ], $user);

            $this->allocatePayment->execute($recorded, $document, (string) $payment['amount']);
        }
    }
}
