<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

class RecalculateDocumentBalance
{
    public function execute(Sale|Purchase $document): void
    {
        DB::transaction(function () use ($document): void {
            $document = $document::query()->lockForUpdate()->findOrFail($document->id);

            $paidAmount = Decimal::sum($document->allocations()->pluck('amount'), 2);
            $balance = Decimal::maxZero(bcsub((string) $document->total, $paidAmount, 2));

            $attributes = [
                'paid_amount' => $paidAmount,
                'balance' => $balance,
            ];

            if ($document instanceof Sale && ! $document->isDraft() && ! $document->isCancelled()) {
                $attributes['status'] = $this->saleStatus($paidAmount, (string) $document->total);
            }

            $document->update($attributes);
        });
    }

    protected function saleStatus(string $paidAmount, string $total): SaleStatus
    {
        if (bccomp($paidAmount, '0', 2) === 0) {
            return SaleStatus::Confirmed;
        }

        if (bccomp($paidAmount, $total, 2) >= 0) {
            return SaleStatus::Paid;
        }

        return SaleStatus::Partial;
    }
}
