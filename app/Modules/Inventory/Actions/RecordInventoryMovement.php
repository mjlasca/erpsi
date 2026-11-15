<?php

namespace App\Modules\Inventory\Actions;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Enums\LotStatus;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Warehouse;
use App\Support\Decimal;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordInventoryMovement
{
    public function execute(
        Product $product,
        Warehouse $warehouse,
        MovementType $movementType,
        string $quantity,
        ?ProductVariant $variant = null,
        ?InventoryLot $lot = null,
        ?string $unitCost = null,
        ?Model $reference = null,
        ?string $notes = null,
        ?User $user = null,
        ?CarbonInterface $movementDate = null,
    ): InventoryMovement {
        if (! $product->is_inventory) {
            throw ValidationException::withMessages([
                'product_id' => 'El producto no maneja inventario.',
            ]);
        }

        if (Decimal::compare($quantity, '0', 3) === 0) {
            throw ValidationException::withMessages([
                'quantity' => 'La cantidad debe ser distinta de cero.',
            ]);
        }

        if ($movementType === MovementType::Quarantine && Decimal::compare($quantity, '0', 3) <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'La cantidad a enviar a cuarentena debe ser mayor que cero.',
            ]);
        }

        if ($movementType === MovementType::Damage && Decimal::compare($quantity, '0', 3) >= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'La baja por daño debe registrarse con cantidad negativa.',
            ]);
        }

        if ($lot !== null) {
            if ($lot->product_id !== $product->id || $lot->product_variant_id !== $variant?->id) {
                throw ValidationException::withMessages([
                    'inventory_lot_id' => 'El lote no corresponde al producto seleccionado.',
                ]);
            }

            if ($lot->warehouse_id !== $warehouse->id) {
                throw ValidationException::withMessages([
                    'inventory_lot_id' => 'El lote no pertenece al almacén seleccionado.',
                ]);
            }

            if ($lot->status === LotStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'inventory_lot_id' => 'El lote está anulado y no puede moverse.',
                ]);
            }
        }

        return DB::transaction(function () use (
            $product,
            $warehouse,
            $movementType,
            $quantity,
            $variant,
            $lot,
            $unitCost,
            $reference,
            $notes,
            $user,
            $movementDate,
        ): InventoryMovement {
            $stock = InventoryStock::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variant?->id)
                ->lockForUpdate()
                ->first();

            if ($stock === null) {
                $stock = InventoryStock::query()->create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity_on_hand' => '0.000',
                    'quantity_reserved' => '0.000',
                    'quantity_minimum' => $product->min_stock,
                    'quantity_quarantined' => '0.000',
                ]);
            }

            $onHand = (string) $stock->quantity_on_hand;
            $reserved = (string) $stock->quantity_reserved;
            $quarantined = (string) ($stock->quantity_quarantined ?? '0.000');
            $available = Decimal::sub(Decimal::sub($onHand, $reserved, 3), $quarantined, 3);

            if ($movementType === MovementType::Quarantine) {
                if (Decimal::compare($quantity, $available, 3) > 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'No hay existencias disponibles para enviar a cuarentena.',
                    ]);
                }

                $stock->update([
                    'quantity_quarantined' => Decimal::round3(Decimal::add($quarantined, $quantity, 3)),
                ]);
            } elseif ($movementType === MovementType::Damage) {
                $damage = Decimal::sub('0', $quantity, 3);
                $newOnHand = Decimal::round3(Decimal::sub($onHand, $damage, 3));
                $newQuarantined = Decimal::round3(Decimal::maxZero(Decimal::sub($quarantined, $damage, 3)));
                $newReserved = $reserved;

                if (Decimal::compare(Decimal::sub($newOnHand, $newReserved, 3), '0', 3) < 0
                    || Decimal::compare($newQuarantined, $newOnHand, 3) > 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'No hay existencias suficientes para registrar la baja.',
                    ]);
                }

                $stock->update([
                    'quantity_on_hand' => $newOnHand,
                    'quantity_quarantined' => $newQuarantined,
                ]);
            } else {
                $newOnHand = Decimal::round3(Decimal::add($onHand, $quantity, 3));

                if (Decimal::compare($newOnHand, '0', 3) < 0
                    || Decimal::compare(Decimal::sub(Decimal::sub($newOnHand, $reserved, 3), $quarantined, 3), '0', 3) < 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'No hay existencias suficientes en el almacén.',
                    ]);
                }

                $stock->update(['quantity_on_hand' => $newOnHand]);
            }

            if ($lot !== null) {
                $lot = InventoryLot::query()->lockForUpdate()->findOrFail($lot->id);
                $newAvailable = $movementType === MovementType::Quarantine
                    ? (string) $lot->quantity_available
                    : Decimal::round3(Decimal::add((string) $lot->quantity_available, $quantity, 3));

                if (Decimal::compare($newAvailable, '0', 3) < 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'No hay cantidad disponible suficiente en el lote.',
                    ]);
                }

                $status = $movementType === MovementType::Quarantine
                    || ($movementType === MovementType::Damage && $lot->status === LotStatus::Quarantine && Decimal::compare($newAvailable, '0', 3) > 0)
                    ? LotStatus::Quarantine
                    : $this->lotStatus($lot, $newAvailable);

                $lot->update([
                    'quantity_available' => $newAvailable,
                    'status' => $status,
                ]);
            }

            return InventoryMovement::query()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'warehouse_id' => $warehouse->id,
                'inventory_lot_id' => $lot?->id,
                'movement_type' => $movementType,
                'quantity' => Decimal::round3($quantity),
                'unit_cost' => $unitCost,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'movement_date' => $movementDate ?? now(),
                'notes' => $notes,
                'created_by' => $user?->id,
            ]);
        });
    }

    protected function lotStatus(InventoryLot $lot, string $newAvailable): LotStatus
    {
        if (Decimal::compare($newAvailable, '0', 3) === 0) {
            return LotStatus::Sold;
        }

        if (Decimal::compare($newAvailable, (string) $lot->quantity_initial, 3) >= 0) {
            return LotStatus::Available;
        }

        return LotStatus::Partial;
    }
}
