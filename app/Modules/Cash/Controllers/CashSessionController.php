<?php

namespace App\Modules\Cash\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cash\Actions\CloseCashSession;
use App\Modules\Cash\Actions\OpenCashSession;
use App\Modules\Cash\Enums\CashSessionStatus;
use App\Modules\Cash\Models\CashSession;
use App\Modules\Cash\Models\InventorySnapshot;
use App\Modules\Cash\Models\InventorySnapshotLine;
use App\Modules\Cash\Requests\CloseCashSessionRequest;
use App\Modules\Cash\Requests\StoreCashSessionRequest;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Resources\PaymentResource;
use App\Support\Decimal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CashSession::class);

        $sessions = CashSession::query()
            ->with(['warehouse', 'opener', 'closer'])
            ->when($request->string('search')->trim()->value(), fn ($query, string $search) => $query->where('session_number', 'like', "%{$search}%"))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->when($request->integer('warehouse_id'), fn ($query, int $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($request->date('from'), fn ($query, $from) => $query->whereDate('business_date', '>=', $from->toDateString()))
            ->when($request->date('to'), fn ($query, $to) => $query->whereDate('business_date', '<=', $to->toDateString()))
            ->latest('opened_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Cash/Sessions/Index', [
            'sessions' => $sessions->through(fn (CashSession $session): array => $this->sessionData($session)),
            'filters' => $request->only('search', 'status', 'warehouse_id', 'from', 'to'),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name', 'code']),
            'statuses' => [
                ['value' => CashSessionStatus::Open->value, 'label' => CashSessionStatus::Open->label()],
                ['value' => CashSessionStatus::Closed->value, 'label' => CashSessionStatus::Closed->label()],
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', CashSession::class);

        return Inertia::render('Cash/Sessions/Create', [
            'warehouses' => Warehouse::query()->active()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function store(StoreCashSessionRequest $request, OpenCashSession $open): RedirectResponse
    {
        $this->authorize('create', CashSession::class);

        $session = $open->execute($request->validated(), $request->user());

        return redirect()
            ->route('cash.sessions.show', $session)
            ->with('success', __('messages.cash_session_opened'));
    }

    public function show(CashSession $cashSession, CloseCashSession $close): Response
    {
        $this->authorize('view', $cashSession);

        $cashSession->load([
            'warehouse',
            'opener',
            'closer',
            'snapshots.lines.product',
            'snapshots.lines.variant',
            'snapshots.lines.warehouse',
        ]);

        $payments = $this->paymentsFor($cashSession);
        $summary = $close->summary($cashSession);
        $opening = $cashSession->snapshots->firstWhere('type', 'OPENING');
        $closing = $cashSession->snapshots->firstWhere('type', 'CLOSING');

        return Inertia::render('Cash/Sessions/Show', [
            'session' => $this->sessionData($cashSession),
            'summary' => [
                ...$summary,
                'counted_cash' => $cashSession->counted_cash,
                'difference' => $cashSession->difference,
            ],
            'payments' => PaymentResource::collection($payments)->resolve(),
            'snapshots' => [
                'opening' => $opening?->only(['id', 'type', 'captured_at']),
                'closing' => $closing?->only(['id', 'type', 'captured_at']),
            ],
            'stockDifferences' => $this->stockDifferences($opening, $closing),
        ]);
    }

    public function close(CloseCashSessionRequest $request, CashSession $cashSession, CloseCashSession $close): RedirectResponse
    {
        $this->authorize('close', $cashSession);

        $close->execute(
            $cashSession,
            (string) $request->input('counted_cash'),
            $request->user(),
            $request->input('notes'),
        );

        return redirect()
            ->route('cash.sessions.show', $cashSession)
            ->with('success', __('messages.cash_session_closed'));
    }

    public function export(CashSession $cashSession, CloseCashSession $close): StreamedResponse
    {
        $this->authorize('view', $cashSession);

        $summary = $close->summary($cashSession);

        return response()->streamDownload(function () use ($cashSession, $summary): void {
            $handle = $this->outputStream();
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                __('exports.cash_session.session'),
                __('exports.cash_session.business_date'),
                __('exports.cash_session.status'),
                __('exports.cash_session.opening_balance'),
                __('exports.cash_session.cash_in'),
                __('exports.cash_session.cash_out'),
                __('exports.cash_session.refunds_out'),
                __('exports.cash_session.refunds_in'),
                __('exports.cash_session.expected_cash'),
                __('exports.cash_session.counted_cash'),
                __('exports.cash_session.difference'),
            ], ';');

            fputcsv($handle, [
                $cashSession->session_number,
                $cashSession->business_date->toDateString(),
                $cashSession->status->label(),
                $cashSession->opening_balance,
                $summary['cash_in'],
                $summary['cash_out'],
                $summary['refunds_out'],
                $summary['refunds_in'],
                $summary['expected_cash'],
                $cashSession->counted_cash ?? '',
                $cashSession->difference ?? '',
            ], ';');

            fclose($handle);
        }, 'arqueo-'.$cashSession->session_number.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportDifferences(CashSession $cashSession): StreamedResponse
    {
        $this->authorize('view', $cashSession);

        $cashSession->load([
            'snapshots.lines.product',
            'snapshots.lines.variant',
            'snapshots.lines.warehouse',
        ]);

        $opening = $cashSession->snapshots->firstWhere('type', 'OPENING');
        $closing = $cashSession->snapshots->firstWhere('type', 'CLOSING');
        $differences = $this->stockDifferences($opening, $closing);

        return response()->streamDownload(function () use ($differences): void {
            $handle = $this->outputStream();
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                __('exports.stock.product'),
                __('exports.stock.sku'),
                __('exports.stock.variant'),
                __('exports.stock.warehouse'),
                __('exports.stock.opening'),
                __('exports.stock.closing'),
                __('exports.stock.difference'),
            ], ';');

            foreach ($differences as $row) {
                fputcsv($handle, [
                    $row['product_name'],
                    $row['sku'],
                    $row['variant_name'],
                    $row['warehouse_name'],
                    $row['opening'],
                    $row['closing'],
                    $row['difference'],
                ], ';');
            }

            fclose($handle);
        }, 'diferencias-'.$cashSession->session_number.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return Collection<int, Payment>
     */
    /** @return resource */
    protected function outputStream()
    {
        $stream = fopen('php://output', 'wb');

        if ($stream === false) {
            throw new \RuntimeException('No se pudo abrir el flujo de salida.');
        }

        return $stream;
    }

    /**
     * @return Collection<int, Payment>
     */
    protected function paymentsFor(CashSession $session): Collection
    {
        return Payment::query()
            ->with(['customer', 'supplier'])
            ->where(function ($query) use ($session): void {
                $query->where('cash_session_id', $session->id)
                    ->orWhere(function ($query) use ($session): void {
                        $query->whereNull('cash_session_id')
                            ->whereDate('payment_date', $session->business_date);
                    });
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function sessionData(CashSession $session): array
    {
        return [
            'id' => $session->id,
            'session_number' => $session->session_number,
            'business_date' => $session->business_date,
            'status' => $session->status->value,
            'status_label' => $session->status->label(),
            'opening_balance' => $session->opening_balance,
            'expected_cash' => $session->expected_cash,
            'counted_cash' => $session->counted_cash,
            'difference' => $session->difference,
            'notes' => $session->notes,
            'opened_at' => $session->opened_at,
            'closed_at' => $session->closed_at,
            'warehouse' => $session->warehouse?->only(['id', 'name', 'code']),
            'opener' => $session->opener?->only(['id', 'name']),
            'closer' => $session->closer?->only(['id', 'name']),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function stockDifferences(?InventorySnapshot $opening, ?InventorySnapshot $closing): array
    {
        $openingLines = ($opening === null ? collect() : collect($opening->lines))->mapWithKeys(
            fn (InventorySnapshotLine $line): array => [$this->snapshotLineKey($line) => $line],
        );
        $closingLines = ($closing === null ? collect() : collect($closing->lines))->mapWithKeys(
            fn (InventorySnapshotLine $line): array => [$this->snapshotLineKey($line) => $line],
        );
        $keys = $openingLines->keys()->merge($closingLines->keys())->unique();

        return $keys->map(function ($key) use ($openingLines, $closingLines): array {
            $open = $openingLines->get($key);
            $close = $closingLines->get($key);
            $line = $open ?? $close;
            $openingQuantity = $open === null ? '0.000' : (string) $open->quantity_on_hand;
            $closingQuantity = $close === null ? '0.000' : (string) $close->quantity_on_hand;

            return [
                'key' => $key,
                'product_name' => $line->product === null ? '—' : $line->product->name,
                'sku' => $line->product === null ? '—' : $line->product->sku,
                'variant_name' => $line->variant === null ? '' : $line->variant->name,
                'warehouse_name' => $line->warehouse === null ? '—' : $line->warehouse->name,
                'opening' => $openingQuantity,
                'closing' => $closingQuantity,
                'difference' => Decimal::sub($closingQuantity, $openingQuantity, 3),
            ];
        })->values()->all();
    }

    protected function snapshotLineKey(InventorySnapshotLine $line): string
    {
        return implode('|', [
            $line->product_id,
            $line->product_variant_id ?? 'null',
            $line->warehouse_id,
        ]);
    }
}
