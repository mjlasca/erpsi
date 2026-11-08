import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField } from '@/types/erp';
import { index, store } from '@/routes/purchasing/suppliers';

export default function SuppliersCreate() {
    const fields: FormField[] = [
        {
            name: 'name',
            label: 'Nombre',
            required: true,
        },
        {
            name: 'company_name',
            label: 'Razón social',
        },
        {
            name: 'tax_id',
            label: 'RFC / Tax ID',
        },
        {
            name: 'email',
            label: 'Correo',
            type: 'email',
        },
        {
            name: 'phone',
            label: 'Teléfono',
        },
        {
            name: 'contact_person',
            label: 'Contacto',
        },
        {
            name: 'address',
            label: 'Dirección',
            className: 'md:col-span-2',
        },
        {
            name: 'notes',
            label: 'Notas',
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
                title="Nuevo proveedor"
                fields={fields}
                initial={{ is_active: true }}
                action={store.url()}
                cancelHref={index.url()}
                submitLabel="Crear proveedor"
            />
        </ErpLayout>
    );
}
