<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Inventory\Actions\RecordInventoryMovement;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelSale
{
    public function __construct(protected RecordInventoryMovement $recordMovement) {}

    public function execute(Sale $sale, ?User $user = null): Sale
    {
        return DB::transaction(function () use ($sale, $user): Sale {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if ($sale->isCancelled()) {
                throw ValidationException::withMessages([
                    'status' => 'La venta ya está cancelada.',
                ]);
            }

            if ($sale->allocations()->exists() || bccomp((string) $sale->paid_amount, '0', 2) > 0) {
                throw ValidationException::withMessages([
                    'status' => 'No se puede cancelar una venta con pagos aplicados.',
                ]);
            }

            if (! $sale->isDraft()) {
                $sale->load('details.product', 'details.variant', 'details.lotAllocations.lot');

                foreach ($sale->details as $detail) {
                    $product = $detail->product;

                    if ($product === null || ! $product->is_inventory) {
                        continue;
                    }

                    if ($product->track_lots) {
                        foreach ($detail->lotAllocations as $allocation) {
                            $this->recordMovement->execute(
                                product: $product,
                                warehouse: $sale->warehouse,
                                movementType: MovementType::Return,
                                quantity: (string) $allocation->quantity,
                                variant: $detail->variant,
                                lot: $allocation->lot,
                                unitCost: (string) $allocation->lot->unit_cost,
                                reference: $sale,
                                notes: "Cancelación de venta {$sale->invoice_number}",
                                user: $user,
                            );
                        }

                        continue;
                    }

                    $this->recordMovement->execute(
                        product: $product,
                        warehouse: $sale->warehouse,
                        movementType: MovementType::Return,
                        quantity: (string) $detail->quantity,
                        variant: $detail->variant,
                        unitCost: (string) $product->cost_price,
                        reference: $sale,
                        notes: "Cancelación de venta {$sale->invoice_number}",
                        user: $user,
                    );
                }
            }

            $sale->update([
                'status' => SaleStatus::Cancelled,
                'cancelled_at' => now(),
                'balance' => '0.00',
            ]);

            return $sale->refresh();
        });
    }
}
