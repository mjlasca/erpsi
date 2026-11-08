import Can from '@/Components/Ui/Can';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex from '@/Components/Ui/ResourceIndex';
import { Badge, DeleteButton, RowLink } from '@/Components/Ui/Primitives';
import type { Supplier } from '@/types/models';
import type { Filters, Paginated } from '@/types/erp';
import {
    create,
    destroy,
    edit,
    index,
    show,
} from '@/routes/purchasing/suppliers';

export default function SuppliersIndex({
    suppliers,
    filters,
}: {
    suppliers: Paginated<Supplier>;
    filters: Filters;
}) {
    return (
        <ErpLayout>
            <ResourceIndex
                title="Proveedores"
                description="Directorio de proveedores."
                items={suppliers}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar por nombre, RFC o correo..."
                createHref={create.url()}
                createPermission="purchasing.suppliers.create"
                createLabel="Nuevo proveedor"
                columns={[
                    {
                        key: 'name',
                        label: 'Proveedor',
                        render: (supplier) => (
                            <div>
                                <p className="font-medium text-gray-900">
                                    {supplier.name}
                                </p>
                                <p className="text-xs text-gray-500">
                                    {supplier.company_name ?? ''}
                                </p>
                            </div>
                        ),
                    },
                    {
                        key: 'tax_id',
                        label: 'RFC / Tax ID',
                        render: (supplier) => supplier.tax_id ?? '—',
                    },
                    {
                        key: 'email',
                        label: 'Correo',
                        render: (supplier) => supplier.email ?? '—',
                    },
                    {
                        key: 'phone',
                        label: 'Teléfono',
                        render: (supplier) => supplier.phone ?? '—',
                    },
                    {
                        key: 'purchases_count',
                        label: 'Compras',
                        render: (supplier) => supplier.purchases_count ?? 0,
                    },
                    {
                        key: 'is_active',
                        label: 'Estado',
                        render: (supplier) => (
                            <Badge tone={supplier.is_active ? 'green' : 'red'}>
                                {supplier.is_active ? 'Activo' : 'Inactivo'}
                            </Badge>
                        ),
                    },
                ]}
                actions={(supplier) => (
                    <>
                        <RowLink href={show.url(supplier.id)}>Ver</RowLink>
                        <Can permission="purchasing.suppliers.update">
                            <RowLink href={edit.url(supplier.id)}>
                                Editar
                            </RowLink>
                        </Can>
                        <Can permission="purchasing.suppliers.delete">
                            <DeleteButton
                                url={destroy.url(supplier.id)}
                                confirmMessage="¿Eliminar este proveedor?"
                            />
                        </Can>
                    </>
                )}
            />
        </ErpLayout>
    );
}
