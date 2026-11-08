import Can from '@/Components/Ui/Can';
import ErpLayout from '@/Layouts/ErpLayout';
import ResourceIndex from '@/Components/Ui/ResourceIndex';
import { Badge, DeleteButton, RowLink } from '@/Components/Ui/Primitives';
import type { Customer } from '@/types/models';
import type { Filters, Paginated } from '@/types/erp';
import { create, destroy, edit, index, show } from '@/routes/sales/customers';

export default function CustomersIndex({
    customers,
    filters,
}: {
    customers: Paginated<Customer>;
    filters: Filters;
}) {
    return (
        <ErpLayout>
            <ResourceIndex
                title="Clientes"
                description="Directorio de clientes y sus datos de contacto."
                items={customers}
                baseUrl={index.url()}
                filters={filters}
                searchPlaceholder="Buscar por nombre, empresa o correo..."
                createHref={create.url()}
                createPermission="sales.customers.create"
                createLabel="Nuevo cliente"
                columns={[
                    {
                        key: 'name',
                        label: 'Cliente',
                        render: (customer) => (
                            <div>
                                <p className="font-medium text-gray-900">
                                    {customer.full_name}
                                </p>
                                <p className="text-xs text-gray-500">
                                    {customer.company_name ?? '—'}
                                </p>
                            </div>
                        ),
                    },
                    {
                        key: 'email',
                        label: 'Correo',
                        render: (customer) => customer.email ?? '—',
                    },
                    {
                        key: 'phone',
                        label: 'Teléfono',
                        render: (customer) => customer.phone ?? '—',
                    },
                    {
                        key: 'tax_id',
                        label: 'RFC',
                        render: (customer) => customer.tax_id ?? '—',
                    },
                    {
                        key: 'is_active',
                        label: 'Estado',
                        render: (customer) => (
                            <Badge tone={customer.is_active ? 'green' : 'gray'}>
                                {customer.is_active ? 'Activo' : 'Inactivo'}
                            </Badge>
                        ),
                    },
                ]}
                actions={(customer) => (
                    <>
                        <RowLink href={show.url(customer.id)}>Ver</RowLink>
                        <Can permission="sales.customers.update">
                            <RowLink href={edit.url(customer.id)}>
                                Editar
                            </RowLink>
                        </Can>
                        <Can permission="sales.customers.delete">
                            <DeleteButton
                                url={destroy.url(customer.id)}
                                confirmMessage="¿Eliminar este cliente?"
                            />
                        </Can>
                    </>
                )}
            />
        </ErpLayout>
    );
}
