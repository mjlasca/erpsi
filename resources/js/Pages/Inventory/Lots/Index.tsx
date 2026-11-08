import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex, { FilterSelect } from '@/Components/Ui/ResourceIndex';
import { Badge, DeleteButton, RowLink } from '@/Components/Ui/Primitives';
import { formatDate, formatNumber } from '@/lib/format';
import type { InventoryLot } from '@/types/models';
import type { Filters, Paginated, SelectOption } from '@/types/erp';
import { toSelectOptions } from '@/types/erp';
import { create, destroy, edit, index, show } from '@/routes/inventory/lots';

const statusTone = (status: string) => {
    switch (status) {
        case 'AVAILABLE':
            return 'green' as const;
        case 'PARTIAL':
            return 'amber' as const;
        case 'CANCELLED':
            return 'red' as const;
        case 'SOLD':
            return 'gray' as const;
        default:
            return 'blue' as const;
    }
};

export default function LotsIndex({
    lots,
    filters,
    products,
    warehouses,
    statuses,
}: {
    lots: Paginated<InventoryLot>;
    filters: Filters;
    products: Array<{ id: number; name: string }>;
    warehouses: Array<{ id: number; name: string; code: string }>;
    statuses: SelectOption[];
}) {
    return (
        <ErpLayout>
            <ResourceIndex
                title="Lotes"
                description="Trazabilidad de inventario por lote."
                items={lots}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar código, lote o serie..."
                createHref={create.url()}
                createLabel="Nuevo lote"
                filterControls={
                    <>
                        <FilterSelect
                            name="product_id"
                            value={filters.product_id?.toString()}
                            placeholder="Todos los productos"
                            options={toSelectOptions(products)}
                        />
                        <FilterSelect
                            name="warehouse_id"
                            value={filters.warehouse_id?.toString()}
                            placeholder="Todos los almacenes"
                            options={warehouses.map((warehouse) => ({
                                value: warehouse.id,
                                label: warehouse.code,
                            }))}
                        />
                        <FilterSelect
                            name="status"
                            value={filters.status?.toString()}
                            placeholder="Todos los estados"
                            options={statuses}
                        />
                    </>
                }
                columns={[
                    {
                        key: 'code',
                        label: 'Código',
                        render: (lot) => (
                            <span className="font-medium text-gray-900">
                                {lot.code}
                            </span>
                        ),
                    },
                    {
                        key: 'product',
                        label: 'Producto',
                        render: (lot) => (
                            <div>
                                <p>{lot.product?.name ?? '—'}</p>
                                <p className="text-xs text-gray-500">
                                    {lot.variant?.name ?? ''}
                                </p>
                            </div>
                        ),
                    },
                    {
                        key: 'warehouse',
                        label: 'Almacén',
                        render: (lot) => lot.warehouse?.name ?? '—',
                    },
                    {
                        key: 'quantity_available',
                        label: 'Disponible / inicial',
                        render: (lot) =>
                            `${formatNumber(lot.quantity_available)} / ${formatNumber(lot.quantity_initial)}`,
                    },
                    {
                        key: 'received_at',
                        label: 'Recibido',
                        render: (lot) => formatDate(lot.received_at),
                    },
                    {
                        key: 'status',
                        label: 'Estado',
                        render: (lot) => (
                            <Badge tone={statusTone(lot.status)}>
                                {lot.status_label}
                            </Badge>
                        ),
                    },
                ]}
                actions={(lot) => (
                    <>
                        <RowLink href={show.url(lot.id)}>Ver</RowLink>
                        <RowLink href={edit.url(lot.id)}>Editar</RowLink>
                        <DeleteButton
                            url={destroy.url(lot.id)}
                            confirmMessage="¿Eliminar este lote?"
                        />
                    </>
                )}
            />
        </ErpLayout>
    );
}
