import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex, { FilterSelect } from '@/Components/Ui/ResourceIndex';
import { Badge, Input, RowLink } from '@/Components/Ui/Primitives';
import { formatDate, formatNumber } from '@/lib/format';
import type { InventoryMovement } from '@/types/models';
import type { Filters, Paginated, SelectOption } from '@/types/erp';
import { create, index, show } from '@/routes/inventory/movements';

const typeTone = (type: string) => {
    switch (type) {
        case 'PURCHASE':
        case 'RETURN':
            return 'green' as const;
        case 'SALE':
            return 'red' as const;
        case 'TRANSFER':
            return 'blue' as const;
        default:
            return 'amber' as const;
    }
};

export default function MovementsIndex({
    movements,
    filters,
    warehouses,
    movementTypes,
}: {
    movements: Paginated<InventoryMovement>;
    filters: Filters;
    warehouses: Array<{ id: number; name: string; code: string }>;
    movementTypes: SelectOption[];
}) {
    return (
        <ErpLayout>
            <ResourceIndex
                title="Movimientos de inventario"
                description="Historial de entradas, salidas, ajustes y transferencias."
                items={movements}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar producto o SKU..."
                createHref={create.url()}
                createPermission="inventory.movements.create"
                createLabel="Registrar movimiento"
                filterControls={
                    <>
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
                            name="movement_type"
                            value={filters.movement_type?.toString()}
                            placeholder="Todos los tipos"
                            options={movementTypes}
                        />
                        <Input
                            type="date"
                            name="from"
                            defaultValue={filters.from?.toString() ?? ''}
                            className="h-10 w-40"
                        />
                        <Input
                            type="date"
                            name="to"
                            defaultValue={filters.to?.toString() ?? ''}
                            className="h-10 w-40"
                        />
                    </>
                }
                columns={[
                    {
                        key: 'movement_date',
                        label: 'Fecha',
                        render: (movement) =>
                            formatDate(movement.movement_date),
                    },
                    {
                        key: 'movement_type',
                        label: 'Tipo',
                        render: (movement) => (
                            <Badge tone={typeTone(movement.movement_type)}>
                                {movement.movement_type_label}
                            </Badge>
                        ),
                    },
                    {
                        key: 'product',
                        label: 'Producto',
                        render: (movement) => (
                            <div>
                                <p className="font-medium text-gray-900">
                                    {movement.product?.name ?? '—'}
                                </p>
                                <p className="text-xs text-gray-500">
                                    {movement.variant?.name ?? ''}
                                </p>
                            </div>
                        ),
                    },
                    {
                        key: 'warehouse',
                        label: 'Almacén',
                        render: (movement) => movement.warehouse?.name ?? '—',
                    },
                    {
                        key: 'lot',
                        label: 'Lote',
                        render: (movement) => movement.lot?.code ?? '—',
                    },
                    {
                        key: 'quantity',
                        label: 'Cantidad',
                        render: (movement) => (
                            <span
                                className={
                                    Number(movement.quantity) < 0
                                        ? 'font-medium text-red-600'
                                        : 'font-medium text-emerald-600'
                                }
                            >
                                {formatNumber(movement.quantity)}
                            </span>
                        ),
                    },
                ]}
                actions={(movement) => (
                    <RowLink href={show.url(movement.id)}>Ver</RowLink>
                )}
            />
        </ErpLayout>
    );
}
