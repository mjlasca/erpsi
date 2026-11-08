import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import {
    Badge,
    Card,
    CardHeader,
    DeleteButton,
    LinkButton,
} from '@/Components/Ui/Primitives';
import type { ProductCategory } from '@/types/models';
import { destroy, edit, index, show } from '@/routes/catalog/categories';
import { Link } from '@inertiajs/react';

export default function CategoriesShow({
    category,
}: {
    category: ProductCategory;
}) {
    return (
        <ErpLayout>
            <ResourceShow
                title={category.name}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            Volver
                        </LinkButton>
                        <LinkButton href={edit.url(category.id)}>
                            Editar
                        </LinkButton>
                        <DeleteButton
                            url={destroy.url(category.id)}
                            confirmMessage="¿Eliminar esta categoría?"
                        />
                    </>
                }
                fields={[
                    {
                        label: 'Categoría padre',
                        value: category.parent?.name ?? '—',
                    },
                    {
                        label: 'Productos',
                        value: category.products_count ?? 0,
                    },
                    {
                        label: 'Subcategorías',
                        value: category.children_count ?? 0,
                    },
                    {
                        label: 'Estado',
                        value: (
                            <Badge tone={category.is_active ? 'green' : 'red'}>
                                {category.is_active ? 'Activa' : 'Inactiva'}
                            </Badge>
                        ),
                    },
                    {
                        label: 'Descripción',
                        value: category.description ?? '—',
                        fullWidth: true,
                    },
                ]}
            >
                <Card>
                    <CardHeader title="Subcategorías" />
                    {!category.children?.length ? (
                        <p className="px-5 py-8 text-center text-sm text-gray-500">
                            Sin subcategorías.
                        </p>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {category.children.map((child) => (
                                <div
                                    key={child.id}
                                    className="flex items-center justify-between px-5 py-3"
                                >
                                    <span className="text-sm text-gray-900">
                                        {child.name}
                                    </span>
                                    <Link
                                        href={show.url(child.id)}
                                        className="text-sm font-medium text-blue-600"
                                    >
                                        Ver
                                    </Link>
                                </div>
                            ))}
                        </div>
                    )}
                </Card>
            </ResourceShow>
        </ErpLayout>
    );
}
