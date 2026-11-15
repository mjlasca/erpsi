<?php

namespace App\Modules\Cash\Actions;

use App\Models\User;
use App\Modules\Cash\Enums\CashSessionStatus;
use App\Modules\Cash\Models\CashSession;
use App\Modules\Inventory\Models\Warehouse;
use App\Support\Decimal;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OpenCashSession
{
    public function __construct(protected CaptureInventorySnapshot $captureSnapshot) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $user): CashSession
    {
        return DB::transaction(function () use ($data, $user): CashSession {
            $alreadyOpen = CashSession::query()
                ->where('status', CashSessionStatus::Open)
                ->where('opened_by', $user->id)
                ->lockForUpdate()
                ->exists();

            if ($alreadyOpen) {
                throw ValidationException::withMessages([
                    'status' => 'Ya tienes una sesión de caja abierta.',
                ]);
            }

            $timezone = app(TenantContext::class)->timezone();
            $tenant = app(TenantContext::class)->tenant();
            $warehouseId = isset($data['warehouse_id']) && $data['warehouse_id'] !== ''
                ? (int) $data['warehouse_id']
                : null;

            if ($warehouseId !== null) {
                Warehouse::query()->findOrFail($warehouseId);
            }

            $session = CashSession::query()->create([
                'session_number' => ($tenant === null ? 'CAJA' : $tenant->cash_session_prefix).'-'.Str::upper((string) Str::ulid()),
                'business_date' => Carbon::now($timezone)->toDateString(),
                'warehouse_id' => $warehouseId,
                'status' => CashSessionStatus::Open,
                'opening_balance' => Decimal::round2((string) ($data['opening_balance'] ?? '0')),
                'opened_by' => $user->id,
                'opened_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->captureSnapshot->execute($session, 'OPENING', $user);

            return $session->refresh();
        });
    }
}
