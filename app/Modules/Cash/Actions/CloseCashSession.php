<?php

namespace App\Modules\Cash\Actions;

use App\Models\User;
use App\Modules\Cash\Enums\CashSessionStatus;
use App\Modules\Cash\Models\CashSession;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentType;
use App\Modules\Payments\Models\Payment;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseCashSession
{
    public function __construct(protected CaptureInventorySnapshot $captureSnapshot) {}

    public function execute(CashSession $session, string $countedCash, ?User $user = null, ?string $notes = null): CashSession
    {
        return DB::transaction(function () use ($session, $countedCash, $user, $notes): CashSession {
            $session = CashSession::query()->lockForUpdate()->findOrFail($session->id);

            if (! $session->isOpen()) {
                throw ValidationException::withMessages([
                    'status' => 'La sesión de caja ya está cerrada.',
                ]);
            }

            $summary = $this->summary($session);
            $counted = Decimal::round2($countedCash);
            $difference = Decimal::sub($counted, $summary['expected_cash'], 2);

            $this->captureSnapshot->execute($session, 'CLOSING', $user);

            $session->update([
                'status' => CashSessionStatus::Closed,
                'counted_cash' => $counted,
                'expected_cash' => $summary['expected_cash'],
                'difference' => $difference,
                'notes' => $notes ?? $session->notes,
                'closed_by' => $user?->id,
                'closed_at' => now(),
            ]);

            return $session->refresh();
        });
    }

    /**
     * @return array{cash_in: string, cash_out: string, refunds_out: string, refunds_in: string, expected_cash: string}
     */
    public function summary(CashSession $session): array
    {
        $query = Payment::query()
            ->where('payment_method', PaymentMethod::Cash)
            ->where(function ($query) use ($session): void {
                $query->where('cash_session_id', $session->id)
                    ->orWhere(function ($query) use ($session): void {
                        $query->whereNull('cash_session_id')
                            ->whereDate('payment_date', $session->business_date);
                    });
            });

        $payments = $query->get(['payment_type', 'amount']);

        $cashIn = Decimal::sum(
            $payments->where('payment_type', PaymentType::CustomerPayment)->pluck('amount'),
            2,
        );
        $cashOut = Decimal::sum(
            $payments->where('payment_type', PaymentType::SupplierPayment)->pluck('amount'),
            2,
        );
        $refundsOut = Decimal::sum(
            $payments->where('payment_type', PaymentType::CustomerRefund)->pluck('amount'),
            2,
        );
        $refundsIn = Decimal::sum(
            $payments->where('payment_type', PaymentType::SupplierRefund)->pluck('amount'),
            2,
        );

        $expected = Decimal::add($session->opening_balance, $cashIn, 2);
        $expected = Decimal::add($expected, $refundsIn, 2);
        $expected = Decimal::sub($expected, $cashOut, 2);
        $expected = Decimal::sub($expected, $refundsOut, 2);

        return [
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'refunds_out' => $refundsOut,
            'refunds_in' => $refundsIn,
            'expected_cash' => $expected,
        ];
    }
}
