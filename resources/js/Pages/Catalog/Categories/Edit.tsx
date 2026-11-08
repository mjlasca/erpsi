import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import type { FormField } from '@/types/erp';
import type { ProductCategory } from '@/types/models';
import { index, update } from '@/routes/catalog/categories';

export default function CategoriesEdit({
    category,
    categories,
}: {
    category: ProductCategory;
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
            options: categories.map((item) => ({
                value: item.id,
                label: item.name,
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
                title={`Editar ${category.name}`}
                fields={fields}
                initial={category as unknown as Record<string, unknown>}
                action={update.url(category.id)}
                method="put"
                cancelHref={index.url()}
                submitLabel="Guardar cambios"
                formKey={`category:${category.id}`}
            />
        </ErpLayout>
    );
}
