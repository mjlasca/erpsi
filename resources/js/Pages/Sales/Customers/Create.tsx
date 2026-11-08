import ErpLayout from '@/Layouts/ErpLayout';
import ResourceForm from '@/Components/Ui/ResourceForm';
import { index, store } from '@/routes/sales/customers';

export default function CustomersCreate() {
    return (
        <ErpLayout>
            <ResourceForm
                title="Nuevo cliente"
                description="Registra los datos de contacto y facturación."
                action={store.url()}
                cancelHref={index.url()}
                submitLabel="Crear cliente"
                fields={[
                    {
                        name: 'first_name',
                        label: 'Nombre',
                        required: true,
                    },
                    {
                        name: 'last_name',
                        label: 'Apellido',
                        required: true,
                    },
                    {
                        name: 'company_name',
                        label: 'Empresa',
                    },
                    {
                        name: 'email',
                        label: 'Correo',
                        type: 'email',
                    },
                    {
                        name: 'phone',
                        label: 'Teléfono',
                    },
                    {
                        name: 'tax_id',
                        label: 'RFC',
                    },
                    {
                        name: 'billing_address',
                        label: 'Dirección de facturación',
                        type: 'textarea',
                        className: 'md:col-span-2',
                    },
                    {
                        name: 'shipping_address',
                        label: 'Dirección de envío',
                        type: 'textarea',
                        className: 'md:col-span-2',
                    },
                    {
                        name: 'notes',
                        label: 'Notas',
                        type: 'textarea',
                        className: 'md:col-span-2',
                    },
                    {
                        name: 'is_active',
                        label: 'Activo',
                        type: 'checkbox',
                        help: 'Permite usar este cliente en nuevos documentos.',
                    },
                ]}
                initial={{ is_active: true }}
            />
        </ErpLayout>
    );
}
