<?php

namespace App\Modules\Cash\Actions;

use App\Models\User;
use App\Modules\Cash\Models\CashSession;
use App\Modules\Cash\Models\InventorySnapshot;
use App\Modules\Cash\Models\InventorySnapshotLine;
use App\Modules\Inventory\Models\InventoryStock;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CaptureInventorySnapshot
{
    public function execute(CashSession $session, string $type, ?User $user = null): InventorySnapshot
    {
        return DB::transaction(function () use ($session, $type, $user): InventorySnapshot {
            $session = CashSession::query()->lockForUpdate()->findOrFail($session->id);

            if ($session->snapshots()->where('type', $type)->exists()) {
                throw ValidationException::withMessages([
                    'type' => 'La instantánea de inventario ya fue generada.',
                ]);
            }

            $snapshot = InventorySnapshot::query()->create([
                'cash_session_id' => $session->id,
                'type' => $type,
                'captured_at' => now(),
                'captured_by' => $user?->id,
            ]);

            $stocks = InventoryStock::query()
                ->when($session->warehouse_id, fn ($query, int $warehouseId) => $query->where('warehouse_id', $warehouseId))
                ->lockForUpdate()
                ->get();

            $tenantId = app(TenantContext::class)->id();
            $now = now();

            foreach ($stocks as $stock) {
                InventorySnapshotLine::query()->create([
                    'tenant_id' => $tenantId,
                    'inventory_snapshot_id' => $snapshot->id,
                    'product_id' => $stock->product_id,
                    'product_variant_id' => $stock->product_variant_id,
                    'warehouse_id' => $stock->warehouse_id,
                    'inventory_lot_id' => null,
                    'quantity_on_hand' => $stock->quantity_on_hand,
                    'quantity_reserved' => $stock->quantity_reserved,
                    'quantity_quarantined' => $stock->quantity_quarantined,
                    'quantity_available' => $stock->quantity_available,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return $snapshot;
        });
    }
}
