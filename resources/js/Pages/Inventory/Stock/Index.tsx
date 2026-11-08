import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex, { FilterSelect } from '@/Components/Ui/ResourceIndex';
import { Badge, RowLink } from '@/Components/Ui/Primitives';
import { formatNumber } from '@/lib/format';
import type { InventoryStock } from '@/types/models';
import type { Filters, Paginated } from '@/types/erp';
import { index, show } from '@/routes/inventory/stock';

export default function StockIndex({
    stock,
    filters,
    warehouses,
}: {
    stock: Paginated<InventoryStock>;
    filters: Filters;
    warehouses: Array<{ id: number; name: string; code: string }>;
}) {
    return (
        <ErpLayout>
            <ResourceIndex
                title="Existencias"
                description="Inventario disponible por producto y almacén."
                items={stock}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar producto o SKU..."
                filterControls={
                    <>
                        <FilterSelect
                            name="warehouse_id"
                            value={filters.warehouse_id?.toString()}
                            placeholder="Todos los almacenes"
                            options={warehouses.map((warehouse) => ({
                                value: warehouse.id,
                                label: `${warehouse.code} · ${warehouse.name}`,
                            }))}
                        />
                        <FilterSelect
                            name="low_stock"
                            value={filters.low_stock?.toString()}
                            placeholder="Todo el inventario"
                            options={[
                                { value: '1', label: 'Solo bajo mínimo' },
                            ]}
                        />
                    </>
                }
                columns={[
                    {
                        key: 'product',
                        label: 'Producto',
                        render: (item) => (
                            <div>
                                <p className="font-medium text-gray-900">
                                    {item.product?.name ?? '—'}
                                </p>
                                <p className="text-xs text-gray-500">
                                    {item.product?.sku}
                                    {item.variant
                                        ? ` · ${item.variant.name}`
                                        : ''}
                                </p>
                            </div>
                        ),
                    },
                    {
                        key: 'warehouse',
                        label: 'Almacén',
                        render: (item) => item.warehouse?.name ?? '—',
                    },
                    {
                        key: 'quantity_on_hand',
                        label: 'En existencia',
                        render: (item) => formatNumber(item.quantity_on_hand),
                    },
                    {
                        key: 'quantity_reserved',
                        label: 'Reservado',
                        render: (item) => formatNumber(item.quantity_reserved),
                    },
                    {
                        key: 'quantity_available',
                        label: 'Disponible',
                        render: (item) => formatNumber(item.quantity_available),
                    },
                    {
                        key: 'quantity_minimum',
                        label: 'Mínimo',
                        render: (item) => formatNumber(item.quantity_minimum),
                    },
                    {
                        key: 'is_low_stock',
                        label: 'Estado',
                        render: (item) => (
                            <Badge tone={item.is_low_stock ? 'amber' : 'green'}>
                                {item.is_low_stock ? 'Bajo mínimo' : 'Normal'}
                            </Badge>
                        ),
                    },
                ]}
                actions={(item) => (
                    <RowLink href={show.url(item.id)}>Ver</RowLink>
                )}
            />
        </ErpLayout>
    );
}
