import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex from '@/Components/Ui/ResourceIndex';
import { Badge, DeleteButton, RowLink } from '@/Components/Ui/Primitives';
import type { ProductCategory } from '@/types/models';
import type { Filters, Paginated } from '@/types/erp';
import {
    create,
    destroy,
    edit,
    index,
    show,
} from '@/routes/catalog/categories';

export default function CategoriesIndex({
    categories,
    filters,
}: {
    categories: Paginated<ProductCategory>;
    filters: Filters;
}) {
    return (
        <ErpLayout>
            <ResourceIndex
                title="Categorías"
                description="Organiza el catálogo por categorías."
                items={categories}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar categoría..."
                createHref={create.url()}
                createLabel="Nueva categoría"
                columns={[
                    {
                        key: 'name',
                        label: 'Nombre',
                        render: (category) => (
                            <span className="font-medium text-gray-900">
                                {category.name}
                            </span>
                        ),
                    },
                    {
                        key: 'parent',
                        label: 'Categoría padre',
                        render: (category) => category.parent?.name ?? '—',
                    },
                    {
                        key: 'products_count',
                        label: 'Productos',
                        render: (category) => category.products_count ?? 0,
                    },
                    {
                        key: 'children_count',
                        label: 'Subcategorías',
                        render: (category) => category.children_count ?? 0,
                    },
                    {
                        key: 'is_active',
                        label: 'Estado',
                        render: (category) => (
                            <Badge tone={category.is_active ? 'green' : 'red'}>
                                {category.is_active ? 'Activa' : 'Inactiva'}
                            </Badge>
                        ),
                    },
                ]}
                actions={(category) => (
                    <>
                        <RowLink href={show.url(category.id)}>Ver</RowLink>
                        <RowLink href={edit.url(category.id)}>Editar</RowLink>
                        <DeleteButton
                            url={destroy.url(category.id)}
                            confirmMessage="¿Eliminar esta categoría?"
                        />
                    </>
                )}
            />
        </ErpLayout>
    );
}
