import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import { Badge, DeleteButton, LinkButton } from '@/Components/Ui/Primitives';
import type { Customer } from '@/types/models';
import { destroy, edit, index } from '@/routes/sales/customers';

export default function CustomersShow({ customer }: { customer: Customer }) {
    return (
        <ErpLayout>
            <ResourceShow
                title={customer.full_name}
                description={customer.company_name ?? 'Cliente'}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            Volver
                        </LinkButton>
                        <LinkButton href={edit.url(customer.id)}>
                            Editar
                        </LinkButton>
                        <DeleteButton
                            url={destroy.url(customer.id)}
                            confirmMessage="¿Eliminar este cliente?"
                        />
                    </>
                }
                fields={[
                    { label: 'Nombre', value: customer.first_name },
                    { label: 'Apellido', value: customer.last_name },
                    {
                        label: 'Empresa',
                        value: customer.company_name ?? '—',
                    },
                    { label: 'Correo', value: customer.email ?? '—' },
                    { label: 'Teléfono', value: customer.phone ?? '—' },
                    { label: 'RFC', value: customer.tax_id ?? '—' },
                    {
                        label: 'Estado',
                        value: (
                            <Badge tone={customer.is_active ? 'green' : 'gray'}>
                                {customer.is_active ? 'Activo' : 'Inactivo'}
                            </Badge>
                        ),
                    },
                    {
                        label: 'Dirección de facturación',
                        value: customer.billing_address ?? '—',
                        fullWidth: true,
                    },
                    {
                        label: 'Dirección de envío',
                        value: customer.shipping_address ?? '—',
                        fullWidth: true,
                    },
                    {
                        label: 'Notas',
                        value: customer.notes ?? '—',
                        fullWidth: true,
                    },
                ]}
            />
        </ErpLayout>
    );
}
