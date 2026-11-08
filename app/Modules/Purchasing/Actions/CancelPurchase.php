<?php

namespace App\Modules\Purchasing\Actions;

use App\Models\User;
use App\Modules\Inventory\Actions\RecordInventoryMovement;
use App\Modules\Inventory\Enums\LotStatus;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Purchasing\Enums\PurchaseStatus;
use App\Modules\Purchasing\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelPurchase
{
    public function __construct(protected RecordInventoryMovement $recordMovement) {}

    public function execute(Purchase $purchase, ?User $user = null): Purchase
    {
        return DB::transaction(function () use ($purchase, $user): Purchase {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if ($purchase->isCancelled()) {
                throw ValidationException::withMessages([
                    'status' => 'La compra ya está cancelada.',
                ]);
            }

            if ($purchase->allocations()->exists() || bccomp((string) $purchase->paid_amount, '0', 2) > 0) {
                throw ValidationException::withMessages([
                    'status' => 'No se puede cancelar una compra con pagos aplicados.',
                ]);
            }

            if ($purchase->isReceived()) {
                $purchase->load('details.product', 'details.variant', 'details.lots');

                foreach ($purchase->details as $detail) {
                    $product = $detail->product;

                    if ($product === null || ! $product->is_inventory) {
                        continue;
                    }

                    if ($product->track_lots) {
                        if ($detail->lots->isEmpty()) {
                            throw ValidationException::withMessages([
                                'status' => 'No se encontraron lotes para revertir la compra.',
                            ]);
                        }

                        foreach ($detail->lots as $lot) {
                            if ($lot->status === LotStatus::Cancelled) {
                                continue;
                            }

                            if (bccomp((string) $lot->quantity_available, (string) $lot->quantity_initial, 3) !== 0) {
                                throw ValidationException::withMessages([
                                    'status' => 'No se puede cancelar una compra cuyo inventario ya fue utilizado.',
                                ]);
                            }

                            $this->recordMovement->execute(
                                product: $product,
                                warehouse: $purchase->warehouse,
                                movementType: MovementType::Return,
                                quantity: bcsub('0', (string) $lot->quantity_available, 3),
                                variant: $detail->variant,
                                lot: $lot,
                                unitCost: (string) $lot->unit_cost,
                                reference: $purchase,
                                notes: "Cancelación de compra {$purchase->purchase_number}",
                                user: $user,
                            );

                            $lot->update(['status' => LotStatus::Cancelled]);
                        }
                    } else {
                        $this->recordMovement->execute(
                            product: $product,
                            warehouse: $purchase->warehouse,
                            movementType: MovementType::Return,
                            quantity: bcsub('0', (string) $detail->quantity, 3),
                            variant: $detail->variant,
                            unitCost: (string) $detail->unit_price,
                            reference: $purchase,
                            notes: "Cancelación de compra {$purchase->purchase_number}",
                            user: $user,
                        );
                    }
                }
            }

            $purchase->update([
                'status' => PurchaseStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            return $purchase->refresh();
        });
    }
}
