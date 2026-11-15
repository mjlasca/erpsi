<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Inventory\Actions\RecordInventoryMovement;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Payments\Actions\RecalculateDocumentBalance;
use App\Modules\Sales\Enums\ReturnStatus;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReturn;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelSaleReturn
{
    public function __construct(
        protected RecordInventoryMovement $recordMovement,
        protected RecalculateDocumentBalance $recalculateBalance,
    ) {}

    public function execute(SaleReturn $return, ?User $user = null): SaleReturn
    {
        return DB::transaction(function () use ($return, $user): SaleReturn {
            $return = SaleReturn::query()->lockForUpdate()->findOrFail($return->id);

            if (! $return->isConfirmed()) {
                throw ValidationException::withMessages([
                    'status' => 'Solo se pueden anular devoluciones confirmadas.',
                ]);
            }

            if (Decimal::compare((string) $return->refunded_amount, '0', 2) > 0) {
                throw ValidationException::withMessages([
                    'status' => 'No se puede anular una devolución con reembolso registrado.',
                ]);
            }

            $sale = Sale::query()->lockForUpdate()->findOrFail($return->sale_id);
            $return->load('details.product');

            $movements = InventoryMovement::query()
                ->where('reference_type', $return->getMorphClass())
                ->where('reference_id', $return->id)
                ->orderBy('id')
                ->get();

            $quarantineKeys = $movements
                ->where('movement_type', MovementType::Quarantine)
                ->map(fn (InventoryMovement $movement): string => $this->movementKey($movement))
                ->all();

            foreach ($movements as $movement) {
                if ($movement->movement_type === MovementType::Quarantine) {
                    $this->recordMovement->execute(
                        product: $movement->product,
                        warehouse: $movement->warehouse,
                        movementType: MovementType::Damage,
                        quantity: Decimal::sub('0', (string) $movement->quantity, 3),
                        variant: $movement->variant,
                        lot: $movement->lot,
                        unitCost: (string) $movement->unit_cost,
                        reference: $return,
                        notes: "Anulación de cuarentena {$return->return_number}",
                        user: $user,
                    );

                    continue;
                }

                if ($movement->movement_type !== MovementType::Return) {
                    continue;
                }

                if (in_array($this->movementKey($movement), $quarantineKeys, true)) {
                    continue;
                }

                $this->recordMovement->execute(
                    product: $movement->product,
                    warehouse: $movement->warehouse,
                    movementType: MovementType::Adjustment,
                    quantity: Decimal::sub('0', (string) $movement->quantity, 3),
                    variant: $movement->variant,
                    lot: $movement->lot,
                    unitCost: (string) $movement->unit_cost,
                    reference: $return,
                    notes: "Anulación de devolución {$return->return_number}",
                    user: $user,
                );
            }

            $sale->update([
                'returned_amount' => Decimal::maxZero(Decimal::sub((string) $sale->returned_amount, (string) $return->total, 2)),
            ]);

            $return->update([
                'status' => ReturnStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            $this->recalculateBalance->execute($sale);

            return $return->refresh();
        });
    }

    protected function movementKey(InventoryMovement $movement): string
    {
        return implode('|', [
            $movement->product_id,
            $movement->product_variant_id ?? 'null',
            $movement->warehouse_id,
            $movement->inventory_lot_id ?? 'null',
        ]);
    }
}
