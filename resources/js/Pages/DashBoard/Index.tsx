import { FormEvent } from 'react';
import { Link, router } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, Package } from 'lucide-react';
import Can from '@/Components/Ui/Can';
import ErpLayout from '@/Layouts/ErpLayout';
import { ComparisonBarChart, DualLineChart } from '@/Components/Ui/Charts';
import {
    Badge,
    Button,
    Card,
    CardHeader,
    PageHeader,
    Select,
} from '@/Components/Ui/Primitives';
import { formatDate, formatMoney, formatNumber } from '@/lib/format';
import { useTranslation } from '@/lib/i18n';
import { useCan } from '@/lib/permissions';
import { dashboard } from '@/routes';
import type { InventoryStock, Purchase, Sale } from '@/types/models';
import { index as purchasesIndex } from '@/routes/purchasing/purchases';
import { index as salesIndex } from '@/routes/sales/sales';
import { index as stockIndex } from '@/routes/inventory/stock';
import { index as productsIndex } from '@/routes/catalog/products';

type Stats = {
    active_products: number;
    active_customers: number;
    active_suppliers: number;
    low_stock_items: number;
    receivable_balance: string;
    payable_balance: string;
    sales_this_month: string;
    purchases_this_month: string;
};

type DashboardCharts = {
    salesPurchases: Array<{
        label: string;
        sales: string;
        purchases: string;
    }>;
    cashFlow: Array<{
        label: string;
        cash_in: string;
        cash_out: string;
    }>;
};

const statusTone = (status: string) => {
    switch (status) {
        case 'PAID':
        case 'RECEIVED':
            return 'green' as const;
        case 'CANCELLED':
            return 'red' as const;
        case 'PARTIAL':
            return 'amber' as const;
        case 'CONFIRMED':
            return 'blue' as const;
        default:
            return 'gray' as const;
    }
};

export default function Dashboard({
    stats,
    recentSales = [],
    recentPurchases = [],
    lowStockItems = [],
    charts,
    filters,
    warehouses = [],
}: {
    stats: Stats;
    recentSales?: Sale[];
    recentPurchases?: Purchase[];
    lowStockItems?: InventoryStock[];
    charts: DashboardCharts;
    filters: { months: number; warehouse_id: number | null };
    warehouses?: Array<{ id: number; name: string; code: string }>;
}) {
    const can = useCan();
    const { t } = useTranslation();
    const cards = [
        {
            label: t('dashboard.activeProducts'),
            value: formatNumber(stats.active_products, 0),
            href: productsIndex.url(),
            permission: 'catalog.products.view',
        },
        {
            label: t('dashboard.lowStock'),
            value: formatNumber(stats.low_stock_items, 0),
            href: stockIndex.url(),
            warning: stats.low_stock_items > 0,
            permission: 'inventory.stock.view',
        },
        {
            label: t('dashboard.receivable'),
            value: formatMoney(stats.receivable_balance),
            href: salesIndex.url(),
            permission: 'sales.sales.view',
        },
        {
            label: t('dashboard.payable'),
            value: formatMoney(stats.payable_balance),
            href: purchasesIndex.url(),
            permission: 'purchasing.purchases.view',
        },
        {
            label: t('dashboard.salesThisMonth'),
            value: formatMoney(stats.sales_this_month),
            href: salesIndex.url(),
            permission: 'sales.sales.view',
        },
        {
            label: t('dashboard.purchasesThisMonth'),
            value: formatMoney(stats.purchases_this_month),
            href: purchasesIndex.url(),
            permission: 'purchasing.purchases.view',
        },
    ].filter((card) => can(card.permission));

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = Object.fromEntries(
            new FormData(event.currentTarget).entries(),
        );
        router.get(dashboard.url(), data, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <ErpLayout>
            <PageHeader
                title={t('dashboard.title')}
                description={t('dashboard.description')}
            />

            <Card className="mb-6 p-4">
                <form
                    onSubmit={applyFilters}
                    className="flex flex-wrap items-end gap-3"
                >
                    <label className="text-sm font-medium text-gray-700">
                        {t('dashboard.filterMonths')}
                        <Select
                            name="months"
                            defaultValue={String(filters.months)}
                            className="mt-1 h-10 w-32"
                        >
                            <option value="6">6</option>
                            <option value="12">12</option>
                        </Select>
                    </label>
                    <label className="text-sm font-medium text-gray-700">
                        {t('dashboard.warehouse')}
                        <Select
                            name="warehouse_id"
                            defaultValue={
                                filters.warehouse_id?.toString() ?? ''
                            }
                            className="mt-1 h-10 w-52"
                        >
                            <option value="">{t('common.none')}</option>
                            {warehouses.map((warehouse) => (
                                <option key={warehouse.id} value={warehouse.id}>
                                    {warehouse.code} · {warehouse.name}
                                </option>
                            ))}
                        </Select>
                    </label>
                    <Button type="submit" variant="secondary">
                        {t('common.filter')}
                    </Button>
                </form>
            </Card>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {cards.map((card) => (
                    <Link key={card.label} href={card.href}>
                        <Card className="p-5 transition-shadow hover:shadow-md">
                            <div className="flex items-start justify-between">
                                <div>
                                    <p className="text-sm text-gray-500">
                                        {card.label}
                                    </p>
                                    <p className="mt-2 text-2xl font-bold text-gray-900">
                                        {card.value}
                                    </p>
                                </div>
                                {card.warning ? (
                                    <AlertTriangle className="h-5 w-5 text-amber-500" />
                                ) : (
                                    <ArrowRight className="h-5 w-5 text-gray-300" />
                                )}
                            </div>
                        </Card>
                    </Link>
                ))}
            </div>

            <div className="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                <Card className="xl:col-span-2">
                    <CardHeader title={t('dashboard.salesVsPurchases')} />
                    <div className="p-5">
                        <ComparisonBarChart
                            data={charts.salesPurchases.map((point) => ({
                                label: point.label,
                                primary: point.sales,
                                secondary: point.purchases,
                            }))}
                            primaryLabel={t('dashboard.sales')}
                            secondaryLabel={t('dashboard.purchases')}
                        />
                    </div>
                </Card>

                <Card className="xl:col-span-2">
                    <CardHeader title={t('dashboard.cashFlow')} />
                    <div className="p-5">
                        <DualLineChart
                            data={charts.cashFlow.map((point) => ({
                                label: point.label,
                                first: point.cash_in,
                                second: point.cash_out,
                            }))}
                            firstLabel={t('dashboard.cashIn')}
                            secondLabel={t('dashboard.cashOut')}
                        />
                    </div>
                </Card>

                <Card>
                    <CardHeader
                        title={t('dashboard.recentSales')}
                        action={
                            <Link
                                href={salesIndex.url()}
                                className="text-sm font-medium text-blue-600 hover:text-blue-700"
                            >
                                Ver todas
                            </Link>
                        }
                    />
                    {recentSales.length === 0 ? (
                        <p className="px-5 py-8 text-center text-sm text-gray-500">
                            Sin ventas registradas.
                        </p>
                    ) : (
                        <ul className="divide-y divide-gray-100">
                            {recentSales.map((sale) => (
                                <li
                                    key={sale.id}
                                    className="flex items-center justify-between gap-3 px-5 py-3"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium text-gray-900">
                                            {sale.invoice_number}
                                        </p>
                                        <p className="truncate text-xs text-gray-500">
                                            {sale.customer?.full_name ??
                                                'Cliente'}{' '}
                                            · {formatDate(sale.sale_date)}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 items-center gap-3">
                                        <Badge tone={statusTone(sale.status)}>
                                            {sale.status_label}
                                        </Badge>
                                        <span className="text-sm font-semibold text-gray-900">
                                            {formatMoney(sale.total)}
                                        </span>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>

                <Can permission="purchasing.purchases.view">
                    <Card>
                        <CardHeader
                            title={t('dashboard.recentPurchases')}
                            action={
                                <Link
                                    href={purchasesIndex.url()}
                                    className="text-sm font-medium text-blue-600 hover:text-blue-700"
                                >
                                    Ver todas
                                </Link>
                            }
                        />
                        {recentPurchases.length === 0 ? (
                            <p className="px-5 py-8 text-center text-sm text-gray-500">
                                Sin compras registradas.
                            </p>
                        ) : (
                            <ul className="divide-y divide-gray-100">
                                {recentPurchases.map((purchase) => (
                                    <li
                                        key={purchase.id}
                                        className="flex items-center justify-between gap-3 px-5 py-3"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-gray-900">
                                                {purchase.purchase_number}
                                            </p>
                                            <p className="truncate text-xs text-gray-500">
                                                {purchase.supplier?.name ??
                                                    'Proveedor'}{' '}
                                                ·{' '}
                                                {formatDate(
                                                    purchase.purchase_date,
                                                )}
                                            </p>
                                        </div>
                                        <div className="flex shrink-0 items-center gap-3">
                                            <Badge
                                                tone={statusTone(
                                                    purchase.status,
                                                )}
                                            >
                                                {purchase.status_label}
                                            </Badge>
                                            <span className="text-sm font-semibold text-gray-900">
                                                {formatMoney(purchase.total)}
                                            </span>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                </Can>

                <Card className="xl:col-span-2">
                    <CardHeader
                        title={t('dashboard.lowStockTitle')}
                        description={t('dashboard.lowStockDescription')}
                        action={
                            <Link
                                href={stockIndex.url()}
                                className="text-sm font-medium text-blue-600 hover:text-blue-700"
                            >
                                Ver inventario
                            </Link>
                        }
                    />
                    {lowStockItems.length === 0 ? (
                        <p className="px-5 py-8 text-center text-sm text-gray-500">
                            Todo el inventario está por encima del mínimo.
                        </p>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {lowStockItems.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex items-center justify-between gap-3 px-5 py-3"
                                >
                                    <div className="flex items-center gap-3">
                                        <Package className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">
                                                {item.product?.name ??
                                                    'Producto'}
                                            </p>
                                            <p className="text-xs text-gray-500">
                                                {item.warehouse?.name} ·{' '}
                                                {item.product?.sku}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-semibold text-amber-600">
                                            {formatNumber(
                                                item.quantity_on_hand,
                                            )}{' '}
                                            /{' '}
                                            {formatNumber(
                                                item.quantity_minimum,
                                            )}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            Disponible:{' '}
                                            {formatNumber(
                                                item.quantity_available,
                                            )}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </Card>
            </div>
        </ErpLayout>
    );
}
