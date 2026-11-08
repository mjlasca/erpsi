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
            $purchase->load('details');

            $subtotal = '0.00';
            $discount = '0.00';
            $taxAmount = '0.00';
            $total = '0.00';

            foreach ($purchase->details as $detail) {
                $gross = Decimal::multiply((string) $detail->quantity, (string) $detail->unit_price, 4);
                $lineSubtotal = Decimal::maxZero(Decimal::sub($gross, (string) $detail->discount, 4), 4);
                $rate = (string) $detail->tax_rate;
                $lineTax = Decimal::round2(Decimal::multiply($lineSubtotal, Decimal::divide($rate, '100', 8), 4));
                $lineTotal = Decimal::add(Decimal::round2($lineSubtotal), $lineTax, 2);

                $detail->update([
                    'tax_rate' => $rate,
                    'tax_amount' => $lineTax,
                    'subtotal' => Decimal::round2($lineSubtotal),
                    'total' => $lineTotal,
                ]);

                $subtotal = Decimal::add($subtotal, Decimal::round2($gross), 2);
                $discount = Decimal::add($discount, Decimal::round2((string) $detail->discount), 2);
                $taxAmount = Decimal::add($taxAmount, $lineTax, 2);
                $total = Decimal::add($total, $lineTotal, 2);
            }

            $balance = Decimal::maxZero(Decimal::sub($total, (string) $purchase->paid_amount, 2));

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
