<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Inventory\Actions\RecordInventoryMovement;
use App\Modules\Inventory\Enums\LotStatus;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\InventoryLot;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleDetail;
use App\Modules\Sales\Models\SaleDetailLotAllocation;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmSale
{
    public function __construct(
        protected RecalculateSaleTotals $recalculateTotals,
        protected RecordInventoryMovement $recordMovement,
    ) {}

    /**
     * @param  array<int, array<int, string>>  $lotAllocations
     */
    public function execute(Sale $sale, array $lotAllocations = [], ?User $user = null): Sale
    {
        return DB::transaction(function () use ($sale, $lotAllocations, $user): Sale {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if (! $sale->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => 'Solo se pueden confirmar ventas en borrador.',
                ]);
            }

            $this->recalculateTotals->execute($sale);
            $sale->load('details.product', 'details.variant');

            if ($sale->details->isEmpty()) {
                throw ValidationException::withMessages([
                    'details' => 'La venta debe tener al menos una línea.',
                ]);
            }

            foreach ($sale->details as $detail) {
                if (Decimal::compare((string) $detail->quantity, '0', 3) <= 0) {
                    throw ValidationException::withMessages([
                        'details' => 'Todas las cantidades deben ser mayores que cero.',
                    ]);
                }

                $product = $detail->product;

                if ($product === null || ! $product->is_inventory) {
                    continue;
                }

                if ($product->track_lots) {
                    $allocations = $this->resolveLotAllocations($sale, $detail, $lotAllocations[$detail->id] ?? []);

                    foreach ($allocations as $lotId => $quantity) {
                        $lot = InventoryLot::query()->lockForUpdate()->findOrFail($lotId);

                        SaleDetailLotAllocation::query()->create([
                            'sale_detail_id' => $detail->id,
                            'inventory_lot_id' => $lot->id,
                            'quantity' => $quantity,
                        ]);

                        $this->recordMovement->execute(
                            product: $product,
                            warehouse: $sale->warehouse,
                            movementType: MovementType::Sale,
                            quantity: Decimal::sub('0', $quantity, 3),
                            variant: $detail->variant,
                            lot: $lot,
                            unitCost: (string) $lot->unit_cost,
                            reference: $sale,
                            notes: "Venta {$sale->invoice_number}",
                            user: $user,
                        );
                    }

                    continue;
                }

                $this->recordMovement->execute(
                    product: $product,
                    warehouse: $sale->warehouse,
                    movementType: MovementType::Sale,
                    quantity: Decimal::sub('0', (string) $detail->quantity, 3),
                    variant: $detail->variant,
                    unitCost: (string) $product->cost_price,
                    reference: $sale,
                    notes: "Venta {$sale->invoice_number}",
                    user: $user,
                );
            }

            $sale->update([
                'status' => SaleStatus::Confirmed,
                'confirmed_at' => now(),
            ]);

            return $sale->refresh();
        });
    }

    /**
     * @param  array<int, string>  $provided
     * @return array<int, string>
     */
    protected function resolveLotAllocations(Sale $sale, SaleDetail $detail, array $provided): array
    {
        if ($provided !== []) {
            $allocations = [];
            $total = '0.000';

            foreach ($provided as $lotId => $quantity) {
                $quantity = (string) $quantity;

                if (Decimal::compare($quantity, '0', 3) <= 0) {
                    throw ValidationException::withMessages([
                        'lot_allocations' => 'Las cantidades asignadas a los lotes deben ser mayores que cero.',
                    ]);
                }

                $lot = InventoryLot::query()->lockForUpdate()->find($lotId);

                if ($lot === null
                    || $lot->product_id !== $detail->product_id
                    || $lot->product_variant_id !== $detail->product_variant_id
                    || $lot->warehouse_id !== $sale->warehouse_id
                    || ! in_array($lot->status, [LotStatus::Available, LotStatus::Partial], true)
                    || Decimal::compare((string) $lot->quantity_available, $quantity, 3) < 0) {
                    throw ValidationException::withMessages([
                        'lot_allocations' => 'Uno de los lotes seleccionados no está disponible en el almacén.',
                    ]);
                }

                $allocations[(int) $lotId] = $quantity;
                $total = Decimal::add($total, $quantity, 3);
            }

            if (Decimal::compare($total, (string) $detail->quantity, 3) !== 0) {
                throw ValidationException::withMessages([
                    'lot_allocations' => 'La cantidad asignada a lotes debe coincidir con la cantidad de la línea.',
                ]);
            }

            return $allocations;
        }

        $remaining = (string) $detail->quantity;
        $allocations = [];

        $lots = InventoryLot::query()
            ->where('product_id', $detail->product_id)
            ->where('product_variant_id', $detail->product_variant_id)
            ->where('warehouse_id', $sale->warehouse_id)
            ->whereIn('status', [LotStatus::Available, LotStatus::Partial])
            ->where('quantity_available', '>', 0)
            ->orderBy('received_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($lots as $lot) {
            if (Decimal::compare($remaining, '0', 3) <= 0) {
                break;
            }

            $take = Decimal::compare((string) $lot->quantity_available, $remaining, 3) >= 0
                ? $remaining
                : (string) $lot->quantity_available;

            if (Decimal::compare($take, '0', 3) <= 0) {
                continue;
            }

            $allocations[$lot->id] = $take;
            $remaining = Decimal::sub($remaining, $take, 3);
        }

        if (Decimal::compare($remaining, '0', 3) > 0) {
            throw ValidationException::withMessages([
                'details' => 'No hay lotes disponibles suficientes para confirmar la venta.',
            ]);
        }

        return $allocations;
    }
}
