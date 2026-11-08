<?php

namespace App\Modules\Purchasing\Actions;

use App\Models\User;
use App\Modules\Inventory\Actions\RecordInventoryMovement;
use App\Modules\Inventory\Enums\LotStatus;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Purchasing\Enums\PurchaseStatus;
use App\Modules\Purchasing\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReceivePurchase
{
    public function __construct(
        protected RecalculatePurchaseTotals $recalculateTotals,
        protected RecordInventoryMovement $recordMovement,
    ) {}

    public function execute(Purchase $purchase, ?User $user = null): Purchase
    {
        return DB::transaction(function () use ($purchase, $user): Purchase {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if (! $purchase->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => 'Solo se pueden recibir compras en borrador.',
                ]);
            }

            $this->recalculateTotals->execute($purchase);
            $purchase->load('details.product', 'details.variant');

            if ($purchase->details->isEmpty()) {
                throw ValidationException::withMessages([
                    'details' => 'La compra debe tener al menos una línea.',
                ]);
            }

            foreach ($purchase->details as $detail) {
                if (bccomp((string) $detail->quantity, '0', 3) <= 0) {
                    throw ValidationException::withMessages([
                        'details' => 'Todas las cantidades deben ser mayores que cero.',
                    ]);
                }

                $product = $detail->product;

                if ($product === null || ! $product->is_inventory) {
                    continue;
                }

                if ($product->track_lots) {
                    $lot = InventoryLot::query()->create([
                        'product_id' => $product->id,
                        'product_variant_id' => $detail->product_variant_id,
                        'warehouse_id' => $purchase->warehouse_id,
                        'purchase_detail_id' => $detail->id,
                        'code' => 'LOT-'.Str::upper((string) Str::ulid()),
                        'quantity_initial' => $detail->quantity,
                        'quantity_available' => $detail->quantity,
                        'unit_cost' => $detail->unit_price,
                        'status' => LotStatus::Available,
                        'received_at' => now(),
                    ]);

                    $this->recordMovement->execute(
                        product: $product,
                        warehouse: $purchase->warehouse,
                        movementType: MovementType::Purchase,
                        quantity: (string) $detail->quantity,
                        variant: $detail->variant,
                        lot: $lot,
                        unitCost: (string) $detail->unit_price,
                        reference: $purchase,
                        notes: "Recepción de compra {$purchase->purchase_number}",
                        user: $user,
                    );
                } else {
                    $this->recordMovement->execute(
                        product: $product,
                        warehouse: $purchase->warehouse,
                        movementType: MovementType::Purchase,
                        quantity: (string) $detail->quantity,
                        variant: $detail->variant,
                        unitCost: (string) $detail->unit_price,
                        reference: $purchase,
                        notes: "Recepción de compra {$purchase->purchase_number}",
                        user: $user,
                    );
                }

                $product->update(['cost_price' => $detail->unit_price]);
            }

            $purchase->update([
                'status' => PurchaseStatus::Received,
                'received_at' => now(),
            ]);

            return $purchase->refresh();
        });
    }
}
