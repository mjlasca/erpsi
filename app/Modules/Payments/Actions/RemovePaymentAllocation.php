<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Payments\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;

class RemovePaymentAllocation
{
    public function __construct(protected RecalculateDocumentBalance $recalculateBalance) {}

    public function execute(PaymentAllocation $allocation): void
    {
        DB::transaction(function () use ($allocation): void {
            $allocation = PaymentAllocation::query()->lockForUpdate()->findOrFail($allocation->id);
            $document = $allocation->sale ?? $allocation->purchase;

            $allocation->delete();

            if ($document !== null) {
                $this->recalculateBalance->execute($document);
            }
        });
    }
}
