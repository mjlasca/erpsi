import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import { Badge, LinkButton } from '@/Components/Ui/Primitives';
import {
    formatDate,
    formatDateTime,
    formatMoney,
    formatNumber,
} from '@/lib/format';
import type { InventoryMovement } from '@/types/models';
import { index } from '@/routes/inventory/movements';

const typeTone = (type: string) => {
    switch (type) {
        case 'PURCHASE':
        case 'RETURN':
            return 'green' as const;
        case 'SALE':
            return 'red' as const;
        case 'TRANSFER':
            return 'blue' as const;
        default:
            return 'amber' as const;
    }
};

export default function MovementsShow({
    movement,
}: {
    movement: InventoryMovement;
}) {
    return (
        <ErpLayout>
            <ResourceShow
                title={`Movimiento #${movement.id}`}
                description={formatDateTime(movement.movement_date)}
                actions={
                    <LinkButton href={index.url()} variant="secondary">
                        Volver
                    </LinkButton>
                }
                fields={[
                    {
                        label: 'Tipo',
                        value: (
                            <Badge tone={typeTone(movement.movement_type)}>
                                {movement.movement_type_label}
                            </Badge>
                        ),
                    },
                    {
                        label: 'Producto',
                        value: movement.product?.name ?? '—',
                    },
                    {
                        label: 'Variante',
                        value: movement.variant?.name ?? '—',
                    },
                    {
                        label: 'Almacén',
                        value: movement.warehouse?.name ?? '—',
                    },
                    {
                        label: 'Lote',
                        value: movement.lot?.code ?? '—',
                    },
                    {
                        label: 'Cantidad',
                        value: formatNumber(movement.quantity),
                    },
                    {
                        label: 'Costo unitario',
                        value: formatMoney(movement.unit_cost),
                    },
                    {
                        label: 'Fecha',
                        value: formatDate(movement.movement_date),
                    },
                    {
                        label: 'Registrado por',
                        value: movement.creator?.name ?? '—',
                    },
                    {
                        label: 'Notas',
                        value: movement.notes ?? '—',
                        fullWidth: true,
                    },
                ]}
            />
        </ErpLayout>
    );
}
