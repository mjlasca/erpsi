import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, Package } from 'lucide-react';
import ErpLayout from '@/Layouts/ErpLayout';
import {
    Badge,
    Card,
    CardHeader,
    PageHeader,
} from '@/Components/Ui/Primitives';
import { formatDate, formatMoney, formatNumber } from '@/lib/format';
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
}: {
    stats: Stats;
    recentSales?: Sale[];
    recentPurchases?: Purchase[];
    lowStockItems?: InventoryStock[];
}) {
    const cards = [
        {
            label: 'Productos activos',
            value: formatNumber(stats.active_products, 0),
            href: productsIndex.url(),
        },
        {
            label: 'Existencias bajas',
            value: formatNumber(stats.low_stock_items, 0),
            href: stockIndex.url(),
            warning: stats.low_stock_items > 0,
        },
        {
            label: 'Por cobrar',
            value: formatMoney(stats.receivable_balance),
            href: salesIndex.url(),
        },
        {
            label: 'Por pagar',
            value: formatMoney(stats.payable_balance),
            href: purchasesIndex.url(),
        },
        {
            label: 'Ventas del mes',
            value: formatMoney(stats.sales_this_month),
            href: salesIndex.url(),
        },
        {
            label: 'Compras del mes',
            value: formatMoney(stats.purchases_this_month),
            href: purchasesIndex.url(),
        },
    ];

    return (
        <ErpLayout>
            <PageHeader
                title="Panel de control"
                description="Resumen general de la operación."
            />

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
                <Card>
                    <CardHeader
                        title="Ventas recientes"
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

                <Card>
                    <CardHeader
                        title="Compras recientes"
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
                                            {formatDate(purchase.purchase_date)}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 items-center gap-3">
                                        <Badge
                                            tone={statusTone(purchase.status)}
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

                <Card className="xl:col-span-2">
                    <CardHeader
                        title="Existencias bajas"
                        description="Productos en o por debajo del mínimo configurado."
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
