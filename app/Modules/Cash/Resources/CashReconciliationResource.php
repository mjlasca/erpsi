<?php

namespace App\Modules\Cash\Resources;

use App\Modules\Cash\Models\CashReconciliation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CashReconciliation
 */
class CashReconciliationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->toDateString(),
            'opening_balance' => $this->opening_balance,
            'counted_cash' => $this->counted_cash,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
        ];
    }
}
