import Can from '@/Components/Ui/Can';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import { Badge, DeleteButton, LinkButton } from '@/Components/Ui/Primitives';
import type { Warehouse } from '@/types/models';
import { destroy, edit, index } from '@/routes/inventory/warehouses';

export default function WarehousesShow({
    warehouse,
}: {
    warehouse: Warehouse;
}) {
    return (
        <ErpLayout>
            <ResourceShow
                title={warehouse.name}
                description={warehouse.code}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            Volver
                        </LinkButton>
                        <Can permission="inventory.warehouses.update">
                            <LinkButton href={edit.url(warehouse.id)}>
                                Editar
                            </LinkButton>
                        </Can>
                        <Can permission="inventory.warehouses.delete">
                            <DeleteButton
                                url={destroy.url(warehouse.id)}
                                confirmMessage="¿Eliminar este almacén?"
                            />
                        </Can>
                    </>
                }
                fields={[
                    {
                        label: 'Código',
                        value: warehouse.code,
                    },
                    {
                        label: 'Dirección',
                        value: warehouse.address ?? '—',
                    },
                    {
                        label: 'Existencias',
                        value: warehouse.stock_items_count ?? 0,
                    },
                    {
                        label: 'Movimientos',
                        value: warehouse.movements_count ?? 0,
                    },
                    {
                        label: 'Estado',
                        value: (
                            <Badge tone={warehouse.is_active ? 'green' : 'red'}>
                                {warehouse.is_active ? 'Activo' : 'Inactivo'}
                            </Badge>
                        ),
                    },
                    {
                        label: 'Descripción',
                        value: warehouse.description ?? '—',
                        fullWidth: true,
                    },
                ]}
            />
        </ErpLayout>
    );
}
