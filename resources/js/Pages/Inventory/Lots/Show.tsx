import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import { Badge, DeleteButton, LinkButton } from '@/Components/Ui/Primitives';
import { formatDate, formatMoney, formatNumber } from '@/lib/format';
import type { InventoryLot } from '@/types/models';
import { destroy, edit, index } from '@/routes/inventory/lots';

const statusTone = (status: string) => {
    switch (status) {
        case 'AVAILABLE':
            return 'green' as const;
        case 'PARTIAL':
            return 'amber' as const;
        case 'CANCELLED':
            return 'red' as const;
        default:
            return 'gray' as const;
    }
};

export default function LotsShow({ lot }: { lot: InventoryLot }) {
    return (
        <ErpLayout>
            <ResourceShow
                title={lot.code}
                description={`${lot.product?.name ?? ''} · ${lot.warehouse?.name ?? ''}`}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            Volver
                        </LinkButton>
                        <LinkButton href={edit.url(lot.id)}>Editar</LinkButton>
                        <DeleteButton
                            url={destroy.url(lot.id)}
                            confirmMessage="¿Eliminar este lote?"
                        />
                    </>
                }
                fields={[
                    {
                        label: 'Producto',
                        value: lot.product?.name ?? '—',
                    },
                    {
                        label: 'Variante',
                        value: lot.variant?.name ?? '—',
                    },
                    {
                        label: 'Almacén',
                        value: lot.warehouse?.name ?? '—',
                    },
                    {
                        label: 'Número de lote',
                        value: lot.lot_number ?? '—',
                    },
                    {
                        label: 'Número de serie',
                        value: lot.serial_number ?? '—',
                    },
                    {
                        label: 'Cantidad inicial',
                        value: formatNumber(lot.quantity_initial),
                    },
                    {
                        label: 'Cantidad disponible',
                        value: formatNumber(lot.quantity_available),
                    },
                    {
                        label: 'Costo unitario',
                        value: formatMoney(lot.unit_cost),
                    },
                    {
                        label: 'Recibido',
                        value: formatDate(lot.received_at),
                    },
                    {
                        label: 'Estado',
                        value: (
                            <Badge tone={statusTone(lot.status)}>
                                {lot.status_label}
                            </Badge>
                        ),
                    },
                    {
                        label: 'Notas',
                        value: lot.notes ?? '—',
                        fullWidth: true,
                    },
                ]}
            />
        </ErpLayout>
    );
}
