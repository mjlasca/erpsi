<?php

namespace App\Support\Exports;

use App\Models\Export;
use App\Modules\Catalog\Models\Product;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Sales\Models\Sale;
use App\Support\CsvExporter;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportBuilder
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function count(string $type, array $filters): int
    {
        return match ($type) {
            'sales' => $this->salesQuery($filters)->count(),
            'purchases' => $this->purchasesQuery($filters)->count(),
            'products' => $this->productsQuery($filters)->count(),
            default => throw new \InvalidArgumentException("Unsupported export type [{$type}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function download(string $type, array $filters): StreamedResponse
    {
        $payload = $this->build($type, $filters);

        return CsvExporter::download($payload['filename'], $payload['headers'], $payload['rows']);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{filename: string, headers: array<int, string>, rows: iterable<int, array<int, string>>}
     */
    public function build(string $type, array $filters): array
    {
        $tenant = app(TenantContext::class)->tenant();
        $dateFormat = $tenant === null ? 'Y-m-d' : $tenant->date_format;

        return match ($type) {
            'sales' => [
                'filename' => 'ventas-'.now()->format('Ymd-His').'.csv',
                'headers' => [
                    __('exports.sales.number'),
                    __('exports.sales.date'),
                    __('exports.sales.customer'),
                    __('exports.sales.warehouse'),
                    __('exports.sales.status'),
                    __('exports.sales.subtotal'),
                    __('exports.sales.discount'),
                    __('exports.sales.tax'),
                    __('exports.sales.total'),
                    __('exports.sales.paid'),
                    __('exports.sales.balance'),
                    __('exports.sales.returned'),
                    __('exports.sales.refunded'),
                ],
                'rows' => $this->salesRows($filters, $dateFormat),
            ],
            'purchases' => [
                'filename' => 'compras-'.now()->format('Ymd-His').'.csv',
                'headers' => [
                    __('exports.purchases.number'),
                    __('exports.purchases.date'),
                    __('exports.purchases.supplier'),
                    __('exports.purchases.supplier_invoice'),
                    __('exports.purchases.warehouse'),
                    __('exports.purchases.status'),
                    __('exports.purchases.subtotal'),
                    __('exports.purchases.discount'),
                    __('exports.purchases.tax'),
                    __('exports.purchases.total'),
                    __('exports.purchases.paid'),
                    __('exports.purchases.balance'),
                    __('exports.purchases.returned'),
                    __('exports.purchases.refunded'),
                ],
                'rows' => $this->purchaseRows($filters, $dateFormat),
            ],
            'products' => [
                'filename' => 'productos-'.now()->format('Ymd-His').'.csv',
                'headers' => [
                    __('exports.products.sku'),
                    __('exports.products.name'),
                    __('exports.products.category'),
                    __('exports.products.unit'),
                    __('exports.products.type'),
                    __('exports.products.brand'),
                    __('exports.products.cost'),
                    __('exports.products.price'),
                    __('exports.products.minimum_stock'),
                    __('exports.products.inventory'),
                    __('exports.products.lots'),
                    __('exports.products.active'),
                ],
                'rows' => $this->productRows($filters),
            ],
            default => throw new \InvalidArgumentException("Unsupported export type [{$type}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return \Generator<int, array<int, string>>
     */
    protected function salesRows(array $filters, string $dateFormat): \Generator
    {
        foreach ($this->salesQuery($filters)->cursor() as $sale) {
            yield [
                $sale->invoice_number,
                $sale->sale_date->format($dateFormat),
                $sale->customer === null ? '' : $sale->customer->full_name,
                $sale->warehouse === null ? '' : $sale->warehouse->name,
                $sale->status->label(),
                (string) $sale->subtotal,
                (string) $sale->discount,
                (string) $sale->tax_amount,
                (string) $sale->total,
                (string) $sale->paid_amount,
                (string) $sale->balance,
                (string) $sale->returned_amount,
                (string) $sale->refunded_amount,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return \Generator<int, array<int, string>>
     */
    protected function purchaseRows(array $filters, string $dateFormat): \Generator
    {
        foreach ($this->purchasesQuery($filters)->cursor() as $purchase) {
            yield [
                $purchase->purchase_number,
                $purchase->purchase_date->format($dateFormat),
                $purchase->supplier === null ? '' : $purchase->supplier->name,
                $purchase->supplier_invoice ?? '',
                $purchase->warehouse === null ? '' : $purchase->warehouse->name,
                $purchase->status->label(),
                (string) $purchase->subtotal,
                (string) $purchase->discount,
                (string) $purchase->tax_amount,
                (string) $purchase->total,
                (string) $purchase->paid_amount,
                (string) $purchase->balance,
                (string) $purchase->returned_amount,
                (string) $purchase->refunded_amount,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return \Generator<int, array<int, string>>
     */
    protected function productRows(array $filters): \Generator
    {
        foreach ($this->productsQuery($filters)->cursor() as $product) {
            yield [
                $product->sku,
                $product->name,
                $product->category === null ? '' : $product->category->name,
                $product->unit === null ? '' : $product->unit->abbreviation,
                $product->product_type->label(),
                $product->brand ?? '',
                (string) $product->cost_price,
                (string) $product->sale_price,
                (string) $product->min_stock,
                $product->is_inventory ? __('common.yes') : __('common.no'),
                $product->track_lots ? __('common.yes') : __('common.no'),
                $product->is_active ? __('common.yes') : __('common.no'),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Sale>
     */
    protected function salesQuery(array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $from = $this->date($filters['from'] ?? null);
        $to = $this->date($filters['to'] ?? null);

        return Sale::query()
            ->with(['customer', 'warehouse'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function (Builder $query) use ($search): void {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(($filters['status'] ?? null), fn (Builder $query, $status) => $query->where('status', $status))
            ->when(($filters['customer_id'] ?? null), fn (Builder $query, $id) => $query->where('customer_id', $id))
            ->when($from, fn (Builder $query, Carbon $date) => $query->whereDate('sale_date', '>=', $date->toDateString()))
            ->when($to, fn (Builder $query, Carbon $date) => $query->whereDate('sale_date', '<=', $date->toDateString()))
            ->latest('sale_date')
            ->latest('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Purchase>
     */
    protected function purchasesQuery(array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $from = $this->date($filters['from'] ?? null);
        $to = $this->date($filters['to'] ?? null);

        return Purchase::query()
            ->with(['supplier', 'warehouse'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('purchase_number', 'like', "%{$search}%")
                        ->orWhere('supplier_invoice', 'like', "%{$search}%");
                });
            })
            ->when(($filters['status'] ?? null), fn (Builder $query, $status) => $query->where('status', $status))
            ->when(($filters['supplier_id'] ?? null), fn (Builder $query, $id) => $query->where('supplier_id', $id))
            ->when($from, fn (Builder $query, Carbon $date) => $query->whereDate('purchase_date', '>=', $date->toDateString()))
            ->when($to, fn (Builder $query, Carbon $date) => $query->whereDate('purchase_date', '<=', $date->toDateString()))
            ->latest('purchase_date')
            ->latest('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Product>
     */
    protected function productsQuery(array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return Product::query()
            ->with(['category', 'unit'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when(($filters['category_id'] ?? null), fn (Builder $query, $id) => $query->where('category_id', $id))
            ->when(array_key_exists('is_active', $filters), fn (Builder $query) => $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOL)))
            ->orderBy('name');
    }

    protected function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $date = Carbon::parse($value);

        return $date->toDateString() === $value ? $date : null;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function queueIfLarge(string $type, array $filters, int $threshold = 2000): ?Export
    {
        if ($this->count($type, $filters) <= $threshold) {
            return null;
        }

        return Export::query()->create([
            'user_id' => auth()->id(),
            'type' => $type,
            'status' => 'PENDING',
            'filters' => $filters,
            'filename' => str($type)->plural()->toString().'-'.now()->format('Ymd-His').'.csv',
        ]);
    }
}
