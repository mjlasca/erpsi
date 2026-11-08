<?php

namespace App\Modules\Inventory\Actions;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\Warehouse;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferInventory
{
    public function __construct(protected RecordInventoryMovement $recordMovement) {}

    public function execute(
        Product $product,
        Warehouse $fromWarehouse,
        Warehouse $toWarehouse,
        string $quantity,
        ?ProductVariant $variant = null,
        ?InventoryLot $lot = null,
        ?string $notes = null,
        ?User $user = null,
    ): InventoryMovement {
        if ($fromWarehouse->is($toWarehouse)) {
            throw ValidationException::withMessages([
                'to_warehouse_id' => 'Los almacenes de origen y destino deben ser distintos.',
            ]);
        }

        if (Decimal::compare($quantity, '0', 3) <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'La cantidad a transferir debe ser mayor que cero.',
            ]);
        }

        if ($product->track_lots && $lot === null) {
            throw ValidationException::withMessages([
                'inventory_lot_id' => 'Debes seleccionar un lote para este producto.',
            ]);
        }

        if ($lot !== null && Decimal::compare($quantity, (string) $lot->quantity_available, 3) !== 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Para transferir un lote debes mover su cantidad disponible completa.',
            ]);
        }

        return DB::transaction(function () use (
            $product,
            $fromWarehouse,
            $toWarehouse,
            $quantity,
            $variant,
            $lot,
            $notes,
            $user,
        ): InventoryMovement {
            $outbound = $this->recordMovement->execute(
                product: $product,
                warehouse: $fromWarehouse,
                movementType: MovementType::Transfer,
                quantity: Decimal::sub('0', $quantity, 3),
                variant: $variant,
                lot: $lot,
                reference: null,
                notes: $notes ?? 'Transferencia entre almacenes',
                user: $user,
            );

            $this->recordMovement->execute(
                product: $product,
                warehouse: $toWarehouse,
                movementType: MovementType::Transfer,
                quantity: $quantity,
                variant: $variant,
                lot: $lot,
                unitCost: $lot?->unit_cost,
                reference: null,
                notes: $notes ?? 'Transferencia entre almacenes',
                user: $user,
            );

            if ($lot !== null) {
                $lot->update(['warehouse_id' => $toWarehouse->id]);
            }

            return $outbound;
        });
    }
}
