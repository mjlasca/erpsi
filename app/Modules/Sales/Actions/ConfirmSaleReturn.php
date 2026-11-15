<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Cash\Models\CashSession;
use App\Modules\Inventory\Actions\RecordInventoryMovement;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Payments\Actions\RecalculateDocumentBalance;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentType;
use App\Modules\Payments\Models\Payment;
use App\Modules\Sales\Enums\ReturnDisposition;
use App\Modules\Sales\Enums\ReturnStatus;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleDetail;
use App\Modules\Sales\Models\SaleReturn;
use App\Modules\Sales\Models\SaleReturnDetail;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmSaleReturn
{
    public function __construct(
        protected RecordInventoryMovement $recordMovement,
        protected RecalculateDocumentBalance $recalculateBalance,
    ) {}

    public function execute(SaleReturn $return, ?User $user = null): SaleReturn
    {
        return DB::transaction(function () use ($return, $user): SaleReturn {
            $return = SaleReturn::query()->lockForUpdate()->findOrFail($return->id);

            if (! $return->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => 'Solo se pueden confirmar devoluciones en borrador.',
                ]);
            }

            $sale = Sale::query()->lockForUpdate()->findOrFail($return->sale_id);

            if (! $sale->isConfirmed()) {
                throw ValidationException::withMessages([
                    'sale_id' => 'Solo se pueden devolver ventas confirmadas.',
                ]);
            }

            $return->load(['details.product', 'details.saleDetail']);
            $total = '0.00';
            $taxAmount = '0.00';
            $subtotal = '0.00';

            foreach ($return->details as $detail) {
                $original = SaleDetail::query()->findOrFail($detail->sale_detail_id);
                $this->validatePendingQuantity($detail, $original);
                $calculated = $this->calculateLine($detail, $original);

                $detail->update($calculated);
                $total = Decimal::add($total, $calculated['total'], 2);
                $taxAmount = Decimal::add($taxAmount, $calculated['tax_amount'], 2);
                $subtotal = Decimal::add($subtotal, $calculated['subtotal'], 2);

                if (! $detail->product?->is_inventory) {
                    continue;
                }

                $product = $detail->product;
                $sale->loadMissing('details');
                $saleDetail = $sale->details->firstWhere('id', $detail->sale_detail_id);
                $lot = $detail->lot;

                if ($product->track_lots && $lot === null) {
                    throw ValidationException::withMessages([
                        'details' => 'Debes indicar el lote de origen para los productos con trazabilidad.',
                    ]);
                }

                if ($detail->disposition === ReturnDisposition::Discard) {
                    continue;
                }

                $this->recordMovement->execute(
                    product: $product,
                    warehouse: $sale->warehouse,
                    movementType: MovementType::Return,
                    quantity: (string) $detail->quantity,
                    variant: $detail->variant,
                    lot: $lot,
                    unitCost: $lot === null ? (string) $product->cost_price : (string) $lot->unit_cost,
                    reference: $return,
                    notes: "Devolución de venta {$return->return_number}",
                    user: $user,
                );

                if ($detail->disposition === ReturnDisposition::Quarantine) {
                    $this->recordMovement->execute(
                        product: $product,
                        warehouse: $sale->warehouse,
                        movementType: MovementType::Quarantine,
                        quantity: (string) $detail->quantity,
                        variant: $detail->variant,
                        lot: $lot,
                        unitCost: $lot === null ? (string) $product->cost_price : (string) $lot->unit_cost,
                        reference: $return,
                        notes: "Devolución enviada a cuarentena {$return->return_number}",
                        user: $user,
                    );
                }
            }

            if (Decimal::compare($total, '0', 2) <= 0) {
                throw ValidationException::withMessages([
                    'details' => 'La devolución debe tener al menos una línea con cantidad.',
                ]);
            }

            $refund = Decimal::round2((string) $return->refunded_amount);
            $credit = Decimal::minPositive($total, (string) $sale->balance);

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
                    'payment_type' => PaymentType::CustomerRefund,
                    'customer_id' => $sale->customer_id,
                    'payment_date' => $return->return_date,
                    'amount' => $refund,
                    'payment_method' => $return->refund_method,
                    'reference' => $return->return_number,
                    'notes' => 'Reembolso de devolución de venta.',
                    'cash_session_id' => $return->refund_method === PaymentMethod::Cash->value
                        ? CashSession::openForDate($return->return_date->toDateString())?->id
                        : null,
                    'created_by' => $user?->id,
                ]);
            }

            $sale->update([
                'returned_amount' => Decimal::add((string) $sale->returned_amount, $total, 2),
                'refunded_amount' => Decimal::add((string) $sale->refunded_amount, $refund, 2),
            ]);

            $return->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'credited_amount' => $credit,
                'status' => ReturnStatus::Confirmed,
                'confirmed_at' => now(),
            ]);

            $this->recalculateBalance->execute($sale);

            return $return->refresh();
        });
    }

    protected function validatePendingQuantity(SaleReturnDetail $detail, SaleDetail $original): void
    {
        if ((int) $detail->product_id !== (int) $original->product_id
            || (int) $detail->product_variant_id !== (int) $original->product_variant_id) {
            throw ValidationException::withMessages([
                'details' => 'La línea devuelta no coincide con la venta original.',
            ]);
        }

        if (Decimal::compare((string) $detail->quantity, '0', 3) <= 0) {
            throw ValidationException::withMessages([
                'details' => 'Las cantidades devueltas deben ser mayores que cero.',
            ]);
        }

        $alreadyReturned = SaleReturnDetail::query()
            ->where('sale_detail_id', $original->id)
            ->whereHas('saleReturn', fn ($query) => $query->where('status', ReturnStatus::Confirmed))
            ->sum('quantity');

        $pending = Decimal::sub((string) $original->quantity, (string) $alreadyReturned, 3);

        if (Decimal::compare((string) $detail->quantity, $pending, 3) > 0) {
            throw ValidationException::withMessages([
                'details' => 'La cantidad devuelta excede lo pendiente de la línea original.',
            ]);
        }

        if ($original->product?->track_lots && $detail->inventory_lot_id !== null) {
            $allocation = $original->lotAllocations()
                ->where('inventory_lot_id', $detail->inventory_lot_id)
                ->first();

            if ($allocation === null) {
                throw ValidationException::withMessages([
                    'details' => 'El lote seleccionado no pertenece a la venta original.',
                ]);
            }

            $returnedForLot = SaleReturnDetail::query()
                ->where('sale_detail_id', $original->id)
                ->where('inventory_lot_id', $detail->inventory_lot_id)
                ->whereHas('saleReturn', fn ($query) => $query->where('status', ReturnStatus::Confirmed))
                ->sum('quantity');

            $pendingForLot = Decimal::sub((string) $allocation->quantity, (string) $returnedForLot, 3);

            if (Decimal::compare((string) $detail->quantity, $pendingForLot, 3) > 0) {
                throw ValidationException::withMessages([
                    'details' => 'La cantidad devuelta excede lo asignado a ese lote.',
                ]);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    protected function calculateLine(SaleReturnDetail $detail, SaleDetail $original): array
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
