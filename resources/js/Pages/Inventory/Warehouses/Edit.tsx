import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField } from '@/types/erp';
import type { Warehouse } from '@/types/models';
import { index, update } from '@/routes/inventory/warehouses';

export default function WarehousesEdit({
    warehouse,
}: {
    warehouse: Warehouse;
}) {
    const fields: FormField[] = [
        {
            name: 'code',
            label: 'Código',
            required: true,
        },
        {
            name: 'name',
            label: 'Nombre',
            required: true,
        },
        {
            name: 'address',
            label: 'Dirección',
        },
        {
            name: 'description',
            label: 'Descripción',
            type: 'textarea',
            className: 'md:col-span-2',
        },
        {
            name: 'is_active',
            label: 'Activo',
            type: 'checkbox',
        },
    ];

    return (
        <ErpLayout>
            <ResourceForm
                title={`Editar ${warehouse.name}`}
                fields={fields}
                initial={warehouse as unknown as Record<string, unknown>}
                action={update.url(warehouse.id)}
                method="put"
                cancelHref={index.url()}
                submitLabel="Guardar cambios"
                formKey={`warehouse:${warehouse.id}`}
            />
        </ErpLayout>
    );
}
