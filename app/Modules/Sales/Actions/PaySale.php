<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Payments\Actions\RecordDocumentPayments;
use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Facades\DB;

class PaySale
{
    public function __construct(
        protected ConfirmSale $confirmSale,
        protected RecordDocumentPayments $recordPayments,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $payments
     */
    public function execute(Sale $sale, array $payments, ?User $user = null): Sale
    {
        return DB::transaction(function () use ($sale, $payments, $user): Sale {
            $confirmed = $this->confirmSale->execute($sale, [], $user);

            $this->recordPayments->execute($confirmed, $payments, $user);

            return $confirmed->refresh();
        });
    }
}
