<?php

namespace App\Modules\Payments\Actions;

use App\Models\User;
use App\Modules\Payments\Enums\PaymentType;
use App\Modules\Payments\Models\Payment;
use App\Support\Decimal;
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

        if (bccomp($amount, '0', 2) <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'El monto del pago debe ser mayor que cero.',
            ]);
        }

        $customerId = $data['customer_id'] ?? null;
        $supplierId = $data['supplier_id'] ?? null;

        if ($type === PaymentType::CustomerPayment) {
            if ($customerId === null) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Debes seleccionar un cliente.',
                ]);
            }

            $supplierId = null;
        }

        if ($type === PaymentType::SupplierPayment) {
            if ($supplierId === null) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'Debes seleccionar un proveedor.',
                ]);
            }

            $customerId = null;
        }

        return DB::transaction(fn (): Payment => Payment::query()->create([
            'payment_type' => $type,
            'customer_id' => $customerId,
            'supplier_id' => $supplierId,
            'payment_date' => $data['payment_date'],
            'amount' => $amount,
            'payment_method' => $data['payment_method'],
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $user?->id,
        ]));
    }
}
