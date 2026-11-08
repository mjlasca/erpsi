<?php

namespace App\Modules\Cash\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cash\Models\CashReconciliation;
use App\Modules\Cash\Requests\UpdateCashReconciliationRequest;
use App\Modules\Cash\Resources\CashReconciliationResource;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentType;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Resources\PaymentResource;
use App\Support\Decimal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CashReconciliationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CashReconciliation::class);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = isset($validated['from'])
            ? Carbon::parse((string) $validated['from'])->startOfDay()
            : Carbon::now()->startOfMonth();
        $to = isset($validated['to'])
            ? Carbon::parse((string) $validated['to'])->startOfDay()
            : Carbon::now()->startOfDay();

        $payments = Payment::query()
            ->whereDate('payment_date', '>=', $from->toDateString())
            ->whereDate('payment_date', '<=', $to->toDateString())
            ->get();

        $reconciliations = CashReconciliation::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn (CashReconciliation $reconciliation): string => $reconciliation->date->toDateString());

        $paymentsByDate = $payments->groupBy(
            fn (Payment $payment): string => Carbon::parse((string) $payment->payment_date)->toDateString(),
        );

        $dates = $paymentsByDate->keys()
            ->merge($reconciliations->keys())
            ->unique()
            ->sortDesc()
            ->values();

        $summaries = $dates
            ->map(fn (int|string $date): array => $this->summaryForDate(
                (string) $date,
                $paymentsByDate->get((string) $date, collect()),
                $reconciliations->get((string) $date),
            ))
            ->values()
            ->all();

        return Inertia::render('Cash/Reconciliations/Index', [
            'summaries' => $summaries,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'totals' => $this->totals($summaries),
        ]);
    }

    public function show(string $date): Response
    {
        $this->authorize('viewAny', CashReconciliation::class);

        $day = $this->parseDate($date);

        $payments = Payment::query()
            ->with(['customer', 'supplier'])
            ->whereDate('payment_date', $day->toDateString())
            ->orderBy('id')
            ->get();

        $reconciliation = CashReconciliation::query()
            ->whereDate('date', $day->toDateString())
            ->first();

        return Inertia::render('Cash/Reconciliations/Show', [
            'date' => $day->toDateString(),
            'summary' => $this->summaryForDate($day->toDateString(), $payments, $reconciliation),
            'payments' => PaymentResource::collection($payments)->resolve(),
            'reconciliation' => $reconciliation === null
                ? null
                : (new CashReconciliationResource($reconciliation))->resolve(),
        ]);
    }

    public function update(
        UpdateCashReconciliationRequest $request,
        string $date,
    ): RedirectResponse {
        $day = $this->parseDate($date);
        $reconciliation = CashReconciliation::query()
            ->whereDate('date', $day->toDateString())
            ->first() ?? new CashReconciliation(['date' => $day]);

        if ($reconciliation->exists) {
            $this->authorize('update', $reconciliation);
        } else {
            $this->authorize('create', CashReconciliation::class);
        }

        $attributes = $request->validated();

        if (! $reconciliation->exists) {
            $attributes['created_by'] = $request->user()?->id;
        }

        $reconciliation->fill($attributes)->save();

        return redirect()
            ->route('cash.reconciliations.show', $day->toDateString())
            ->with('success', 'Arqueo guardado correctamente.');
    }

    /**
     * @param  Collection<int, Payment>  $payments
     * @return array<string, mixed>
     */
    protected function summaryForDate(
        string $date,
        Collection $payments,
        ?CashReconciliation $reconciliation,
    ): array {
        $cashIn = Decimal::sum(
            $payments->filter(
                fn (Payment $payment): bool => $payment->payment_type === PaymentType::CustomerPayment
                    && $payment->payment_method === PaymentMethod::Cash,
            )->pluck('amount'),
            2,
        );

        $cashOut = Decimal::sum(
            $payments->filter(
                fn (Payment $payment): bool => $payment->payment_type === PaymentType::SupplierPayment
                    && $payment->payment_method === PaymentMethod::Cash,
            )->pluck('amount'),
            2,
        );

        $inflow = Decimal::sum(
            $payments->filter(
                fn (Payment $payment): bool => $payment->payment_type === PaymentType::CustomerPayment,
            )->pluck('amount'),
            2,
        );

        $outflow = Decimal::sum(
            $payments->filter(
                fn (Payment $payment): bool => $payment->payment_type === PaymentType::SupplierPayment,
            )->pluck('amount'),
            2,
        );

        $opening = Decimal::round2((string) ($reconciliation->opening_balance ?? '0'));
        $expected = Decimal::sub(Decimal::add($opening, $cashIn, 2), $cashOut, 2);
        $counted = $reconciliation?->counted_cash;
        $difference = $counted === null
            ? null
            : Decimal::sub($counted, $expected, 2);

        return [
            'date' => $date,
            'opening_balance' => $opening,
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'inflow' => $inflow,
            'outflow' => $outflow,
            'net_flow' => Decimal::sub($inflow, $outflow, 2),
            'expected_cash' => $expected,
            'counted_cash' => $counted,
            'difference' => $difference,
            'reconciled' => $reconciliation !== null,
            'notes' => $reconciliation?->notes,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $summaries
     * @return array<string, string>
     */
    protected function totals(array $summaries): array
    {
        return [
            'cash_in' => Decimal::sum(array_map(fn (array $summary): string => (string) $summary['cash_in'], $summaries), 2),
            'cash_out' => Decimal::sum(array_map(fn (array $summary): string => (string) $summary['cash_out'], $summaries), 2),
            'inflow' => Decimal::sum(array_map(fn (array $summary): string => (string) $summary['inflow'], $summaries), 2),
            'outflow' => Decimal::sum(array_map(fn (array $summary): string => (string) $summary['outflow'], $summaries), 2),
        ];
    }

    protected function parseDate(string $date): Carbon
    {
        $parsed = Carbon::createFromFormat('!Y-m-d', $date);

        if ($parsed === null || $parsed->toDateString() !== $date) {
            abort(404);
        }

        return $parsed;
    }
}
