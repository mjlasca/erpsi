<?php

namespace App\Modules\Purchasing\Actions;

use App\Models\User;
use App\Modules\Inventory\Actions\RecordInventoryMovement;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Payments\Actions\RecalculateDocumentBalance;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Purchasing\Models\PurchaseReturn;
use App\Modules\Sales\Enums\ReturnStatus;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelPurchaseReturn
{
    public function __construct(
        protected RecordInventoryMovement $recordMovement,
        protected RecalculateDocumentBalance $recalculateBalance,
    ) {}

    public function execute(PurchaseReturn $return, ?User $user = null): PurchaseReturn
    {
        return DB::transaction(function () use ($return, $user): PurchaseReturn {
            $return = PurchaseReturn::query()->lockForUpdate()->findOrFail($return->id);

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

            $purchase = Purchase::query()->lockForUpdate()->findOrFail($return->purchase_id);

            $movements = InventoryMovement::query()
                ->where('reference_type', $return->getMorphClass())
                ->where('reference_id', $return->id)
                ->where('movement_type', MovementType::Return)
                ->get();

            foreach ($movements as $movement) {
                $this->recordMovement->execute(
                    product: $movement->product,
                    warehouse: $movement->warehouse,
                    movementType: MovementType::Return,
                    quantity: Decimal::sub('0', (string) $movement->quantity, 3),
                    variant: $movement->variant,
                    lot: $movement->lot,
                    unitCost: (string) $movement->unit_cost,
                    reference: $return,
                    notes: "Anulación de devolución de compra {$return->return_number}",
                    user: $user,
                );
            }

            $purchase->update([
                'returned_amount' => Decimal::maxZero(Decimal::sub((string) $purchase->returned_amount, (string) $return->total, 2)),
            ]);

            $return->update([
                'status' => ReturnStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            $this->recalculateBalance->execute($purchase);

            return $return->refresh();
        });
    }
}
