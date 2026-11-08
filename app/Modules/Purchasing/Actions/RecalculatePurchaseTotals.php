<?php

namespace App\Modules\Purchasing\Actions;

use App\Modules\Purchasing\Models\Purchase;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

class RecalculatePurchaseTotals
{
    public function execute(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase): Purchase {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);
            $purchase->load('details.taxRate');

            $subtotal = '0.00';
            $discount = '0.00';
            $taxAmount = '0.00';
            $total = '0.00';

            foreach ($purchase->details as $detail) {
                $gross = bcmul((string) $detail->quantity, (string) $detail->unit_price, 4);
                $lineSubtotal = Decimal::maxZero(bcsub($gross, (string) $detail->discount, 4), 4);
                $rate = (string) ($detail->taxRate?->rate ?? $detail->tax_rate);
                $lineTax = Decimal::round2(bcmul($lineSubtotal, bcdiv($rate, '100', 8), 4));
                $lineTotal = bcadd(Decimal::round2($lineSubtotal), $lineTax, 2);

                $detail->update([
                    'tax_rate' => $rate,
                    'tax_amount' => $lineTax,
                    'subtotal' => Decimal::round2($lineSubtotal),
                    'total' => $lineTotal,
                ]);

                $subtotal = bcadd($subtotal, Decimal::round2($gross), 2);
                $discount = bcadd($discount, Decimal::round2((string) $detail->discount), 2);
                $taxAmount = bcadd($taxAmount, $lineTax, 2);
                $total = bcadd($total, $lineTotal, 2);
            }

            $balance = Decimal::maxZero(bcsub($total, (string) $purchase->paid_amount, 2));

            $purchase->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'balance' => $balance,
            ]);

            return $purchase->refresh();
        });
    }
}
