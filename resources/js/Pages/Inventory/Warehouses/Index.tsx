import Can from '@/Components/Ui/Can';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex from '@/Components/Ui/ResourceIndex';
import { Badge, DeleteButton, RowLink } from '@/Components/Ui/Primitives';
import type { Warehouse } from '@/types/models';
import type { Filters, Paginated } from '@/types/erp';
import {
    create,
    destroy,
    edit,
    index,
    show,
} from '@/routes/inventory/warehouses';

export default function WarehousesIndex({
    warehouses,
    filters,
}: {
    warehouses: Paginated<Warehouse>;
    filters: Filters;
}) {
    return (
        <ErpLayout>
            <ResourceIndex
                title="Almacenes"
                description="Ubicaciones físicas de inventario."
                items={warehouses}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar almacén..."
                createHref={create.url()}
                createPermission="inventory.warehouses.create"
                createLabel="Nuevo almacén"
                columns={[
                    {
                        key: 'code',
                        label: 'Código',
                        render: (warehouse) => (
                            <span className="font-medium text-gray-900">
                                {warehouse.code}
                            </span>
                        ),
                    },
                    {
                        key: 'name',
                        label: 'Nombre',
                    },
                    {
                        key: 'address',
                        label: 'Dirección',
                        render: (warehouse) => warehouse.address ?? '—',
                    },
                    {
                        key: 'stock_items_count',
                        label: 'Existencias',
                        render: (warehouse) => warehouse.stock_items_count ?? 0,
                    },
                    {
                        key: 'is_active',
                        label: 'Estado',
                        render: (warehouse) => (
                            <Badge tone={warehouse.is_active ? 'green' : 'red'}>
                                {warehouse.is_active ? 'Activo' : 'Inactivo'}
                            </Badge>
                        ),
                    },
                ]}
                actions={(warehouse) => (
                    <>
                        <RowLink href={show.url(warehouse.id)}>Ver</RowLink>
                        <Can permission="inventory.warehouses.update">
                            <RowLink href={edit.url(warehouse.id)}>
                                Editar
                            </RowLink>
                        </Can>
                        <Can permission="inventory.warehouses.delete">
                            <DeleteButton
                                url={destroy.url(warehouse.id)}
                                confirmMessage="¿Eliminar este almacén?"
                            />
                        </Can>
                    </>
                )}
            />
        </ErpLayout>
    );
}
