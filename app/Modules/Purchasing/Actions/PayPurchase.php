<?php

namespace App\Modules\Purchasing\Actions;

use App\Models\User;
use App\Modules\Payments\Actions\RecordDocumentPayments;
use App\Modules\Purchasing\Enums\PurchaseStatus;
use App\Modules\Purchasing\Models\Purchase;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

class PayPurchase
{
    public function __construct(
        protected ReceivePurchase $receivePurchase,
        protected RecordDocumentPayments $recordPayments,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $payments
     */
    public function execute(Purchase $purchase, array $payments, ?User $user = null): Purchase
    {
        return DB::transaction(function () use ($purchase, $payments, $user): Purchase {
            $received = $this->receivePurchase->execute($purchase, $user);

            $this->recordPayments->execute($received, $payments, $user);

            $paid = $received->refresh();

            if (Decimal::compare((string) $paid->paid_amount, (string) $paid->total, 2) >= 0) {
                $paid->update(['status' => PurchaseStatus::Paid]);
            }

            return $paid->refresh();
        });
    }
}
