import { Link } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';
import {
    Badge,
    Card,
    DeleteButton,
    LinkButton,
    PageHeader,
} from '@/Components/Ui/Primitives';
import { formatMoney } from '@/lib/format';
import { create, destroy, edit } from '@/routes/platform/plans';

type Plan = {
    id: number;
    name: string;
    price: string;
    currency: string;
    billing_period: string;
    max_users: number | null;
    max_products: number | null;
    max_warehouses: number | null;
    max_monthly_sales: number | null;
    features: string[];
    is_active: boolean;
    tenants_count: number;
};

const limitLabel = (value: number | null) => (value === null ? '∞' : value);

export default function PlatformPlansIndex({ plans }: { plans: Plan[] }) {
    return (
        <PlatformLayout>
            <PageHeader
                title="Planes"
                description="Capacidad, precio y límites por plan."
                actions={
                    <LinkButton href={create.url()}>Nuevo plan</LinkButton>
                }
            />
            <Card>
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-100 text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Plan
                                </th>
                                <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Precio
                                </th>
                                <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Límites
                                </th>
                                <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Empresas
                                </th>
                                <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Estado
                                </th>
                                <th className="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {plans.map((plan) => (
                                <tr key={plan.id}>
                                    <td className="px-5 py-3">
                                        <p className="font-medium text-gray-900">
                                            {plan.name}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            {plan.features.join(' · ')}
                                        </p>
                                    </td>
                                    <td className="px-5 py-3">
                                        {formatMoney(plan.price)} /{' '}
                                        {plan.billing_period === 'YEARLY'
                                            ? 'año'
                                            : 'mes'}
                                    </td>
                                    <td className="px-5 py-3 text-xs text-gray-600">
                                        {limitLabel(plan.max_users)} usuarios ·{' '}
                                        {limitLabel(plan.max_products)}{' '}
                                        productos ·{' '}
                                        {limitLabel(plan.max_warehouses)}{' '}
                                        almacenes ·{' '}
                                        {limitLabel(plan.max_monthly_sales)}{' '}
                                        ventas/mes
                                    </td>
                                    <td className="px-5 py-3">
                                        {plan.tenants_count}
                                    </td>
                                    <td className="px-5 py-3">
                                        <Badge
                                            tone={
                                                plan.is_active
                                                    ? 'green'
                                                    : 'gray'
                                            }
                                        >
                                            {plan.is_active
                                                ? 'Activo'
                                                : 'Inactivo'}
                                        </Badge>
                                    </td>
                                    <td className="px-5 py-3 text-right">
                                        <div className="flex justify-end gap-2">
                                            <Link
                                                href={edit.url(plan.id)}
                                                className="rounded-lg px-3 py-1.5 text-sm font-medium text-blue-600 hover:bg-blue-50"
                                            >
                                                Editar
                                            </Link>
                                            <DeleteButton
                                                url={destroy.url(plan.id)}
                                                confirmMessage="¿Eliminar este plan?"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>
        </PlatformLayout>
    );
}
