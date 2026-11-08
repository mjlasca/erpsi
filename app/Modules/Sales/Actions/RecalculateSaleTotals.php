<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

class RecalculateSaleTotals
{
    public function execute(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale): Sale {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            $sale->load('details');

            $subtotal = '0.00';
            $discount = '0.00';
            $taxAmount = '0.00';
            $total = '0.00';

            foreach ($sale->details as $detail) {
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

            $paidAmount = Decimal::sum($sale->allocations()->pluck('amount'), 2);
            $balance = Decimal::maxZero(Decimal::sub($total, $paidAmount, 2));

            $attributes = [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'balance' => $balance,
            ];

            if (! $sale->isDraft() && ! $sale->isCancelled()) {
                $attributes['status'] = $this->statusFor($paidAmount, $total);
            }

            $sale->update($attributes);

            return $sale->refresh();
        });
    }

    protected function statusFor(string $paidAmount, string $total): SaleStatus
    {
        if (Decimal::compare($paidAmount, '0', 2) === 0) {
            return SaleStatus::Confirmed;
        }

        if (Decimal::compare($paidAmount, $total, 2) >= 0) {
            return SaleStatus::Paid;
        }

        return SaleStatus::Partial;
    }
}
