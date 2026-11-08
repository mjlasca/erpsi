import ErpLayout from '@/Layouts/ErpLayout';
import ResourceShow from '@/Components/Ui/ResourceShow';
import { Badge, LinkButton } from '@/Components/Ui/Primitives';
import { formatNumber } from '@/lib/format';
import type { InventoryStock } from '@/types/models';
import { index } from '@/routes/inventory/stock';

export default function StockShow({ stock }: { stock: InventoryStock }) {
    return (
        <ErpLayout>
            <ResourceShow
                title={stock.product?.name ?? 'Existencia'}
                description={`${stock.product?.sku ?? ''} · ${stock.warehouse?.name ?? ''}`}
                actions={
                    <LinkButton href={index.url()} variant="secondary">
                        Volver
                    </LinkButton>
                }
                fields={[
                    {
                        label: 'Producto',
                        value: stock.product?.name ?? '—',
                    },
                    {
                        label: 'Variante',
                        value: stock.variant?.name ?? '—',
                    },
                    {
                        label: 'Almacén',
                        value: stock.warehouse?.name ?? '—',
                    },
                    {
                        label: 'En existencia',
                        value: formatNumber(stock.quantity_on_hand),
                    },
                    {
                        label: 'Reservado',
                        value: formatNumber(stock.quantity_reserved),
                    },
                    {
                        label: 'Disponible',
                        value: formatNumber(stock.quantity_available),
                    },
                    {
                        label: 'Mínimo',
                        value: formatNumber(stock.quantity_minimum),
                    },
                    {
                        label: 'Estado',
                        value: (
                            <Badge
                                tone={stock.is_low_stock ? 'amber' : 'green'}
                            >
                                {stock.is_low_stock ? 'Bajo mínimo' : 'Normal'}
                            </Badge>
                        ),
                    },
                ]}
            />
        </ErpLayout>
    );
}
