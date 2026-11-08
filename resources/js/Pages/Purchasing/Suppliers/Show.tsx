import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import { Badge, DeleteButton, LinkButton } from '@/Components/Ui/Primitives';
import type { Supplier } from '@/types/models';
import { destroy, edit, index } from '@/routes/purchasing/suppliers';

export default function SuppliersShow({ supplier }: { supplier: Supplier }) {
    return (
        <ErpLayout>
            <ResourceShow
                title={supplier.name}
                description={supplier.company_name ?? undefined}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            Volver
                        </LinkButton>
                        <LinkButton href={edit.url(supplier.id)}>
                            Editar
                        </LinkButton>
                        <DeleteButton
                            url={destroy.url(supplier.id)}
                            confirmMessage="¿Eliminar este proveedor?"
                        />
                    </>
                }
                fields={[
                    {
                        label: 'Razón social',
                        value: supplier.company_name ?? '—',
                    },
                    {
                        label: 'RFC / Tax ID',
                        value: supplier.tax_id ?? '—',
                    },
                    {
                        label: 'Correo',
                        value: supplier.email ?? '—',
                    },
                    {
                        label: 'Teléfono',
                        value: supplier.phone ?? '—',
                    },
                    {
                        label: 'Contacto',
                        value: supplier.contact_person ?? '—',
                    },
                    {
                        label: 'Compras',
                        value: supplier.purchases_count ?? 0,
                    },
                    {
                        label: 'Pagos',
                        value: supplier.payments_count ?? 0,
                    },
                    {
                        label: 'Estado',
                        value: (
                            <Badge tone={supplier.is_active ? 'green' : 'red'}>
                                {supplier.is_active ? 'Activo' : 'Inactivo'}
                            </Badge>
                        ),
                    },
                    {
                        label: 'Dirección',
                        value: supplier.address ?? '—',
                        fullWidth: true,
                    },
                    {
                        label: 'Notas',
                        value: supplier.notes ?? '—',
                        fullWidth: true,
                    },
                ]}
            />
        </ErpLayout>
    );
}
