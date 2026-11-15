<?php

namespace App\Modules\Purchasing\Actions;

use App\Models\User;
use App\Modules\Cash\Models\CashSession;
use App\Modules\Inventory\Actions\RecordInventoryMovement;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Payments\Actions\RecalculateDocumentBalance;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentType;
use App\Modules\Payments\Models\Payment;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Purchasing\Models\PurchaseDetail;
use App\Modules\Purchasing\Models\PurchaseReturn;
use App\Modules\Purchasing\Models\PurchaseReturnDetail;
use App\Modules\Sales\Enums\ReturnStatus;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmPurchaseReturn
{
    public function __construct(
        protected RecordInventoryMovement $recordMovement,
        protected RecalculateDocumentBalance $recalculateBalance,
    ) {}

    public function execute(PurchaseReturn $return, ?User $user = null): PurchaseReturn
    {
        return DB::transaction(function () use ($return, $user): PurchaseReturn {
            $return = PurchaseReturn::query()->lockForUpdate()->findOrFail($return->id);

            if (! $return->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => 'Solo se pueden confirmar devoluciones en borrador.',
                ]);
            }

            $purchase = Purchase::query()->lockForUpdate()->findOrFail($return->purchase_id);

            if (! $purchase->isReceived()) {
                throw ValidationException::withMessages([
                    'purchase_id' => 'Solo se pueden devolver compras recibidas.',
                ]);
            }

            $return->load(['details.product', 'details.purchaseDetail']);
            $total = '0.00';
            $taxAmount = '0.00';
            $subtotal = '0.00';

            foreach ($return->details as $detail) {
                $original = PurchaseDetail::query()->findOrFail($detail->purchase_detail_id);
                $this->validatePendingQuantity($detail, $original);
                $calculated = $this->calculateLine($detail, $original);
                $detail->update($calculated);

                $total = Decimal::add($total, $calculated['total'], 2);
                $taxAmount = Decimal::add($taxAmount, $calculated['tax_amount'], 2);
                $subtotal = Decimal::add($subtotal, $calculated['subtotal'], 2);

                if (! $detail->product?->is_inventory) {
                    continue;
                }

                $lot = $detail->lot;

                if ($detail->product->track_lots && $lot === null) {
                    throw ValidationException::withMessages([
                        'details' => 'Debes indicar el lote de origen para los productos con trazabilidad.',
                    ]);
                }

                if ($lot !== null && Decimal::compare((string) $lot->quantity_available, (string) $detail->quantity, 3) < 0) {
                    throw ValidationException::withMessages([
                        'details' => 'El lote no tiene cantidad disponible suficiente para la devolución.',
                    ]);
                }

                $this->recordMovement->execute(
                    product: $detail->product,
                    warehouse: $purchase->warehouse,
                    movementType: MovementType::Return,
                    quantity: Decimal::sub('0', (string) $detail->quantity, 3),
                    variant: $detail->variant,
                    lot: $lot,
                    unitCost: $lot === null ? (string) $original->unit_price : (string) $lot->unit_cost,
                    reference: $return,
                    notes: "Devolución de compra {$return->return_number}",
                    user: $user,
                );
            }

            if (Decimal::compare($total, '0', 2) <= 0) {
                throw ValidationException::withMessages([
                    'details' => 'La devolución debe tener al menos una línea con cantidad.',
                ]);
            }

            $refund = Decimal::round2((string) $return->refunded_amount);
            $credit = Decimal::minPositive($total, (string) $purchase->balance);

            if (Decimal::compare(Decimal::add($credit, $refund, 2), $total, 2) > 0) {
                throw ValidationException::withMessages([
                    'refunded_amount' => 'El reembolso no puede exceder el total de la devolución.',
                ]);
            }

            if (Decimal::compare($refund, '0', 2) > 0) {
                if ($return->refund_method === null) {
                    throw ValidationException::withMessages([
                        'refund_method' => 'Selecciona el método del reembolso.',
                    ]);
                }

                Payment::query()->create([
                    'payment_type' => PaymentType::SupplierRefund,
                    'supplier_id' => $purchase->supplier_id,
                    'payment_date' => $return->return_date,
                    'amount' => $refund,
                    'payment_method' => $return->refund_method,
                    'reference' => $return->return_number,
                    'notes' => 'Reembolso de proveedor por devolución de compra.',
                    'cash_session_id' => $return->refund_method === PaymentMethod::Cash->value
                        ? CashSession::openForDate($return->return_date->toDateString())?->id
                        : null,
                    'created_by' => $user?->id,
                ]);
            }

            $purchase->update([
                'returned_amount' => Decimal::add((string) $purchase->returned_amount, $total, 2),
                'refunded_amount' => Decimal::add((string) $purchase->refunded_amount, $refund, 2),
            ]);

            $return->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'credited_amount' => $credit,
                'status' => ReturnStatus::Confirmed,
                'confirmed_at' => now(),
            ]);

            $this->recalculateBalance->execute($purchase);

            return $return->refresh();
        });
    }

    protected function validatePendingQuantity(PurchaseReturnDetail $detail, PurchaseDetail $original): void
    {
        if ((int) $detail->product_id !== (int) $original->product_id
            || (int) $detail->product_variant_id !== (int) $original->product_variant_id) {
            throw ValidationException::withMessages([
                'details' => 'La línea devuelta no coincide con la compra original.',
            ]);
        }

        if (Decimal::compare((string) $detail->quantity, '0', 3) <= 0) {
            throw ValidationException::withMessages([
                'details' => 'Las cantidades devueltas deben ser mayores que cero.',
            ]);
        }

        $alreadyReturned = PurchaseReturnDetail::query()
            ->where('purchase_detail_id', $original->id)
            ->whereHas('purchaseReturn', fn ($query) => $query->where('status', ReturnStatus::Confirmed))
            ->sum('quantity');

        $pending = Decimal::sub((string) $original->quantity, (string) $alreadyReturned, 3);

        if (Decimal::compare((string) $detail->quantity, $pending, 3) > 0) {
            throw ValidationException::withMessages([
                'details' => 'La cantidad devuelta excede lo pendiente de la línea original.',
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function calculateLine(PurchaseReturnDetail $detail, PurchaseDetail $original): array
    {
        $ratio = Decimal::compare((string) $original->quantity, '0', 3) === 0
            ? '0'
            : Decimal::divide((string) $detail->quantity, (string) $original->quantity, 8);

        $gross = Decimal::multiply((string) $detail->quantity, (string) $original->unit_price, 4);
        $discount = Decimal::round2(Decimal::multiply((string) $original->discount, $ratio, 4));
        $lineSubtotal = Decimal::maxZero(Decimal::sub($gross, $discount, 4), 4);
        $tax = Decimal::round2(Decimal::multiply(
            $lineSubtotal,
            Decimal::divide((string) $original->tax_rate, '100', 8),
            4,
        ));
        $total = Decimal::add(Decimal::round2($lineSubtotal), $tax, 2);

        return [
            'unit_price' => (string) $original->unit_price,
            'discount' => $discount,
            'tax_rate' => (string) $original->tax_rate,
            'subtotal' => Decimal::round2($lineSubtotal),
            'tax_amount' => $tax,
            'total' => $total,
        ];
    }
}
