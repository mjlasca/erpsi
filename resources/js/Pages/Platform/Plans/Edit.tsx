import PlatformLayout from '@/Layouts/PlatformLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField } from '@/types/erp';
import { index, update } from '@/routes/platform/plans';

const fields: FormField[] = [
    { name: 'name', label: 'Nombre', required: true },
    { name: 'slug', label: 'Identificador' },
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

export default function PlatformPlansEdit({
    plan,
}: {
    plan: {
        id: number;
        name: string;
        slug: string;
        price: string;
        currency: string;
        billing_period: string;
        max_users: number | null;
        max_products: number | null;
        max_warehouses: number | null;
        max_monthly_sales: number | null;
        sort_order: number;
        description: string | null;
        features_text: string;
        is_active: boolean;
    };
}) {
    return (
        <PlatformLayout>
            <ResourceForm
                title="Editar plan"
                description="Actualiza precio, capacidad y límites."
                fields={fields}
                initial={plan}
                action={update.url(plan.id)}
                method="put"
                cancelHref={index.url()}
                submitLabel="Guardar plan"
            />
        </PlatformLayout>
    );
}
