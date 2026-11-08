import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField } from '@/types/erp';
import type { Supplier } from '@/types/models';
import { index, update } from '@/routes/purchasing/suppliers';

export default function SuppliersEdit({ supplier }: { supplier: Supplier }) {
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
                title={`Editar ${supplier.name}`}
                fields={fields}
                initial={supplier as unknown as Record<string, unknown>}
                action={update.url(supplier.id)}
                method="put"
                cancelHref={index.url()}
                submitLabel="Guardar cambios"
                formKey={`supplier:${supplier.id}`}
            />
        </ErpLayout>
    );
}
