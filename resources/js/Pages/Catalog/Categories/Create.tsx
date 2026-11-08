import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField } from '@/types/erp';
import { index, store } from '@/routes/catalog/categories';

export default function CategoriesCreate({
    categories,
}: {
    categories: Array<{ id: number; name: string }>;
}) {
    const fields: FormField[] = [
        {
            name: 'name',
            label: 'Nombre',
            required: true,
        },
        {
            name: 'parent_id',
            label: 'Categoría padre',
            type: 'select',
            options: categories.map((category) => ({
                value: category.id,
                label: category.name,
            })),
        },
        {
            name: 'description',
            label: 'Descripción',
            type: 'textarea',
            className: 'md:col-span-2',
        },
        {
            name: 'is_active',
            label: 'Activa',
            type: 'checkbox',
        },
    ];

    return (
        <ErpLayout>
            <ResourceForm
                title="Nueva categoría"
                fields={fields}
                initial={{ is_active: true }}
                action={store.url()}
                cancelHref={index.url()}
                submitLabel="Crear categoría"
            />
        </ErpLayout>
    );
}
