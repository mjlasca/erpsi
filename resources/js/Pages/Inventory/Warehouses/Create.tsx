import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField } from '@/types/erp';
import { index, store } from '@/routes/inventory/warehouses';

export default function WarehousesCreate() {
    const fields: FormField[] = [
        {
            name: 'code',
            label: 'Código',
            required: true,
            placeholder: 'Ej. ALM-01',
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
                title="Nuevo almacén"
                fields={fields}
                initial={{ is_active: true }}
                action={store.url()}
                cancelHref={index.url()}
                submitLabel="Crear almacén"
            />
        </ErpLayout>
    );
}
