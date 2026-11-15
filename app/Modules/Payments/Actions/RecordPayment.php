<?php

namespace App\Modules\Payments\Actions;

use App\Models\User;
use App\Modules\Cash\Models\CashSession;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentType;
use App\Modules\Payments\Models\Payment;
use App\Support\Decimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordPayment
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?User $user = null): Payment
    {
        $type = $data['payment_type'] instanceof PaymentType
            ? $data['payment_type']
            : PaymentType::from((string) $data['payment_type']);

        $amount = Decimal::round2((string) $data['amount']);

        if (Decimal::compare($amount, '0', 2) <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'El monto del pago debe ser mayor que cero.',
            ]);
        }

        $customerId = $data['customer_id'] ?? null;
        $supplierId = $data['supplier_id'] ?? null;

        if (in_array($type, [PaymentType::CustomerPayment, PaymentType::CustomerRefund], true)) {
            if ($customerId === null) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Debes seleccionar un cliente.',
                ]);
            }

            $supplierId = null;
        }

        if (in_array($type, [PaymentType::SupplierPayment, PaymentType::SupplierRefund], true)) {
            if ($supplierId === null) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'Debes seleccionar un proveedor.',
                ]);
            }

            $customerId = null;
        }

        $paymentMethod = $data['payment_method'] instanceof PaymentMethod
            ? $data['payment_method']
            : PaymentMethod::from((string) $data['payment_method']);

        return DB::transaction(function () use ($type, $customerId, $supplierId, $data, $amount, $paymentMethod, $user): Payment {
            $cashSessionId = null;

            if ($paymentMethod === PaymentMethod::Cash) {
                $cashSessionId = CashSession::openForDate(
                    Carbon::parse((string) $data['payment_date'])->toDateString(),
                )?->id;
            }

            return Payment::query()->create([
                'payment_type' => $type,
                'customer_id' => $customerId,
                'supplier_id' => $supplierId,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'cash_session_id' => $cashSessionId,
                'created_by' => $user?->id,
            ]);
        });
    }
}
