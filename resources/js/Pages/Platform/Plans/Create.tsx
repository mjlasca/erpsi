import PlatformLayout from '@/Layouts/PlatformLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField } from '@/types/erp';
import { index, store } from '@/routes/platform/plans';

const fields: FormField[] = [
    { name: 'name', label: 'Nombre', required: true },
    {
        name: 'slug',
        label: 'Identificador',
        help: 'Se genera del nombre si se deja vacío.',
    },
    {
        name: 'price',
        label: 'Precio',
        type: 'number',
        step: '0.01',
        min: '0',
        required: true,
    },
    { name: 'currency', label: 'Moneda', required: true },
    {
        name: 'billing_period',
        label: 'Periodo',
        type: 'select',
        required: true,
        options: [
            { value: 'MONTHLY', label: 'Mensual' },
            { value: 'YEARLY', label: 'Anual' },
        ],
    },
    {
        name: 'max_users',
        label: 'Máximo de usuarios',
        type: 'number',
        min: '0',
        help: 'Vacío significa ilimitado.',
    },
    {
        name: 'max_products',
        label: 'Máximo de productos',
        type: 'number',
        min: '0',
        help: 'Vacío significa ilimitado.',
    },
    {
        name: 'max_warehouses',
        label: 'Máximo de almacenes',
        type: 'number',
        min: '0',
        help: 'Vacío significa ilimitado.',
    },
    {
        name: 'max_monthly_sales',
        label: 'Máximo de ventas por mes',
        type: 'number',
        min: '0',
        help: 'Vacío significa ilimitado.',
    },
    { name: 'sort_order', label: 'Orden', type: 'number', min: '0' },
    {
        name: 'description',
        label: 'Descripción',
        type: 'textarea',
        className: 'md:col-span-2',
    },
    {
        name: 'features',
        label: 'Características',
        type: 'textarea',
        help: 'Una característica por línea.',
        className: 'md:col-span-2',
    },
    {
        name: 'is_active',
        label: 'Activo',
        type: 'checkbox',
        help: 'Mostrar este plan como disponible.',
    },
];

export default function PlatformPlansCreate() {
    return (
        <PlatformLayout>
            <ResourceForm
                title="Nuevo plan"
                description="Define precio, capacidad y límites."
                fields={fields}
                initial={{
                    currency: 'MXN',
                    billing_period: 'MONTHLY',
                    is_active: true,
                    sort_order: 0,
                }}
                action={store.url()}
                cancelHref={index.url()}
                submitLabel="Crear plan"
            />
        </PlatformLayout>
    );
}
