import { FormEvent } from 'react';
import { router } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';
import {
    Badge,
    Button,
    Card,
    CardHeader,
    ConfirmButton,
    Field,
    Input,
    LinkButton,
    PageHeader,
    Select,
    Spinner,
    Textarea,
} from '@/Components/Ui/Primitives';
import { formatDate, formatDateTime, formatMoney } from '@/lib/format';
import {
    impersonate,
    index,
    reactivate,
    show,
    suspend,
} from '@/routes/platform/tenants';
import { update as updateSubscription } from '@/routes/platform/subscriptions';
import { useForm } from '@inertiajs/react';

type Usage = {
    users: number;
    products: number;
    warehouses: number;
    sales_this_month: number;
    limits: Record<string, number | null>;
    percentages: Record<string, number | null>;
};

export default function PlatformTenantShow({
    tenant,
    usage,
    plans,
    subscriptions,
    recentAudit,
}: {
    tenant: {
        id: number;
        name: string;
        slug: string;
        legal_name: string | null;
        tax_id: string | null;
        email: string | null;
        phone: string | null;
        address: string | null;
        locale: string;
        timezone: string;
        currency: string;
        date_format: string;
        is_active: boolean;
        suspended_at: string | null;
        suspension_reason: string | null;
        subscription_status: string;
        trial_ends_at: string | null;
        subscription_starts_at: string | null;
        subscription_ends_at: string | null;
        billing_email: string | null;
        created_at: string;
        owner?: { id: number; name: string; email: string } | null;
        plan?: {
            id: number;
            name: string;
            price: string;
            currency: string;
            billing_period: string;
        } | null;
        default_warehouse?: { id: number; name: string; code: string } | null;
    };
    usage: Usage;
    plans: Array<{ id: number; name: string; price: string; currency: string }>;
    subscriptions: Array<{
        id: number;
        status: string;
        starts_at: string;
        ends_at: string | null;
        trial_ends_at: string | null;
        amount: string;
        currency: string;
        notes: string | null;
        plan?: { id: number; name: string } | null;
        creator?: { id: number; name: string } | null;
        created_at: string;
    }>;
    recentAudit: Array<{
        id: number;
        action: string;
        description: string;
        user?: { id: number; name: string } | null;
        created_at: string;
    }>;
}) {
    const subscriptionForm = useForm({
        plan_id: tenant.plan ? String(tenant.plan.id) : '',
        status: tenant.subscription_status,
        billing_email: tenant.billing_email ?? '',
        trial_ends_at: tenant.trial_ends_at?.slice(0, 10) ?? '',
        subscription_starts_at:
            tenant.subscription_starts_at?.slice(0, 10) ?? '',
        subscription_ends_at: tenant.subscription_ends_at?.slice(0, 10) ?? '',
        notes: '',
    });
    const suspendForm = useForm({ reason: '' });

    const submitSubscription = (event: FormEvent) => {
        event.preventDefault();
        subscriptionForm.put(updateSubscription.url(tenant.id), {
            preserveScroll: true,
        });
    };

    const submitSuspend = (event: FormEvent) => {
        event.preventDefault();
        suspendForm.post(suspend.url(tenant.id), { preserveScroll: true });
    };

    const usageCards = [
        { key: 'users', label: 'Usuarios' },
        { key: 'products', label: 'Productos' },
        { key: 'warehouses', label: 'Almacenes' },
        { key: 'sales_this_month', label: 'Ventas del mes' },
    ];

    return (
        <PlatformLayout>
            <PageHeader
                title={tenant.name}
                description={`${tenant.slug} · ${tenant.plan?.name ?? 'Sin plan'}`}
                actions={
                    <>
                        <LinkButton href={index.url()} variant="secondary">
                            Volver
                        </LinkButton>
                        {tenant.is_active ? (
                            <ConfirmButton
                                message="¿Suspender esta empresa? Sus usuarios no podrán entrar al ERP."
                                confirmLabel="Suspender"
                                onConfirm={() =>
                                    router.post(
                                        suspend.url(tenant.id),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Suspender
                            </ConfirmButton>
                        ) : (
                            <ConfirmButton
                                message="¿Reactivar esta empresa?"
                                confirmLabel="Reactivar"
                                tone="primary"
                                variant="primary"
                                onConfirm={() =>
                                    router.post(
                                        reactivate.url(tenant.id),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Reactivar
                            </ConfirmButton>
                        )}
                        {tenant.is_active && (
                            <ConfirmButton
                                message="Entrarás a esta empresa con permisos de administrador y quedará registrado en auditoría."
                                title="Entrar en modo soporte"
                                confirmLabel="Entrar como soporte"
                                tone="primary"
                                variant="primary"
                                onConfirm={() =>
                                    router.post(impersonate.url(tenant.id))
                                }
                            >
                                Entrar como soporte
                            </ConfirmButton>
                        )}
                    </>
                }
            />

            {!tenant.is_active && (
                <Card className="mb-6 border-red-200 bg-red-50 p-5">
                    <p className="font-medium text-red-800">
                        Empresa suspendida
                    </p>
                    <p className="mt-1 text-sm text-red-700">
                        {tenant.suspension_reason || 'Sin motivo registrado.'}
                        {tenant.suspended_at
                            ? ` · ${formatDateTime(tenant.suspended_at)}`
                            : ''}
                    </p>
                </Card>
            )}

            <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <Card className="xl:col-span-2">
                    <CardHeader
                        title="Uso y límites"
                        description="Consumo actual frente al plan asignado."
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        {usageCards.map((item) => {
                            const used = usage[
                                item.key as keyof Usage
                            ] as number;
                            const limit = usage.limits[item.key] ?? null;
                            const percent = usage.percentages[item.key] ?? null;

                            return (
                                <div
                                    key={item.key}
                                    className="rounded-lg border border-gray-100 p-4"
                                >
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="font-medium text-gray-700">
                                            {item.label}
                                        </span>
                                        <span className="text-gray-500">
                                            {used}
                                            {limit === null
                                                ? ' / ∞'
                                                : ` / ${limit}`}
                                        </span>
                                    </div>
                                    <div className="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                                        <div
                                            className={`h-full rounded-full ${percent !== null && percent >= 90 ? 'bg-red-500' : 'bg-blue-500'}`}
                                            style={{
                                                width: `${percent ?? 0}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </Card>

                <Card>
                    <CardHeader title="Suscripción" />
                    <form
                        onSubmit={submitSubscription}
                        className="space-y-4 p-5"
                    >
                        <Field
                            label="Plan"
                            htmlFor="plan_id"
                            error={subscriptionForm.errors.plan_id}
                        >
                            <Select
                                id="plan_id"
                                value={subscriptionForm.data.plan_id}
                                onChange={(e) =>
                                    subscriptionForm.setData(
                                        'plan_id',
                                        e.target.value,
                                    )
                                }
                            >
                                <option value="">Sin plan</option>
                                {plans.map((plan) => (
                                    <option key={plan.id} value={plan.id}>
                                        {plan.name} · {formatMoney(plan.price)}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field
                            label="Estado"
                            htmlFor="status"
                            error={subscriptionForm.errors.status}
                        >
                            <Select
                                id="status"
                                value={subscriptionForm.data.status}
                                onChange={(e) =>
                                    subscriptionForm.setData(
                                        'status',
                                        e.target.value,
                                    )
                                }
                            >
                                <option value="TRIALING">Prueba</option>
                                <option value="ACTIVE">Activa</option>
                                <option value="PAST_DUE">Pago pendiente</option>
                                <option value="CANCELLED">Cancelada</option>
                                <option value="SUSPENDED">Suspendida</option>
                            </Select>
                        </Field>
                        <Field
                            label="Correo de facturación"
                            htmlFor="billing_email"
                            error={subscriptionForm.errors.billing_email}
                        >
                            <Input
                                id="billing_email"
                                type="email"
                                value={subscriptionForm.data.billing_email}
                                onChange={(e) =>
                                    subscriptionForm.setData(
                                        'billing_email',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Fin de prueba"
                            htmlFor="trial_ends_at"
                            error={subscriptionForm.errors.trial_ends_at}
                        >
                            <Input
                                id="trial_ends_at"
                                type="date"
                                value={subscriptionForm.data.trial_ends_at}
                                onChange={(e) =>
                                    subscriptionForm.setData(
                                        'trial_ends_at',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Inicio"
                            htmlFor="subscription_starts_at"
                            error={
                                subscriptionForm.errors.subscription_starts_at
                            }
                        >
                            <Input
                                id="subscription_starts_at"
                                type="date"
                                value={
                                    subscriptionForm.data.subscription_starts_at
                                }
                                onChange={(e) =>
                                    subscriptionForm.setData(
                                        'subscription_starts_at',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Fin"
                            htmlFor="subscription_ends_at"
                            error={subscriptionForm.errors.subscription_ends_at}
                        >
                            <Input
                                id="subscription_ends_at"
                                type="date"
                                value={
                                    subscriptionForm.data.subscription_ends_at
                                }
                                onChange={(e) =>
                                    subscriptionForm.setData(
                                        'subscription_ends_at',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Notas del cambio"
                            htmlFor="notes"
                            error={subscriptionForm.errors.notes}
                        >
                            <Textarea
                                id="notes"
                                value={subscriptionForm.data.notes}
                                onChange={(e) =>
                                    subscriptionForm.setData(
                                        'notes',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={subscriptionForm.processing}
                        >
                            {subscriptionForm.processing && <Spinner />}
                            Actualizar suscripción
                        </Button>
                    </form>
                </Card>

                <Card className="xl:col-span-2">
                    <CardHeader title="Datos de la empresa" />
                    <dl className="grid grid-cols-1 gap-4 p-5 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-gray-500">Razón social</dt>
                            <dd className="font-medium text-gray-900">
                                {tenant.legal_name ?? '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">
                                Identificación fiscal
                            </dt>
                            <dd className="font-medium text-gray-900">
                                {tenant.tax_id ?? '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Correo</dt>
                            <dd className="font-medium text-gray-900">
                                {tenant.email ?? '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Teléfono</dt>
                            <dd className="font-medium text-gray-900">
                                {tenant.phone ?? '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Propietario</dt>
                            <dd className="font-medium text-gray-900">
                                {tenant.owner?.name ?? '—'}{' '}
                                {tenant.owner?.email
                                    ? `· ${tenant.owner.email}`
                                    : ''}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Almacén principal</dt>
                            <dd className="font-medium text-gray-900">
                                {tenant.default_warehouse?.code ?? '—'} ·{' '}
                                {tenant.default_warehouse?.name ?? ''}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">
                                Idioma / zona horaria
                            </dt>
                            <dd className="font-medium text-gray-900">
                                {tenant.locale} · {tenant.timezone}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Creada</dt>
                            <dd className="font-medium text-gray-900">
                                {formatDate(tenant.created_at)}
                            </dd>
                        </div>
                    </dl>
                </Card>

                <Card>
                    <CardHeader title="Suspender empresa" />
                    <form onSubmit={submitSuspend} className="space-y-4 p-5">
                        <Field
                            label="Motivo"
                            htmlFor="reason"
                            error={suspendForm.errors.reason}
                        >
                            <Textarea
                                id="reason"
                                value={suspendForm.data.reason}
                                onChange={(e) =>
                                    suspendForm.setData(
                                        'reason',
                                        e.target.value,
                                    )
                                }
                                placeholder="Motivo de la suspensión"
                            />
                        </Field>
                        <Button
                            type="submit"
                            variant="danger"
                            className="w-full"
                            disabled={
                                !tenant.is_active || suspendForm.processing
                            }
                        >
                            {suspendForm.processing && <Spinner />}
                            Suspender
                        </Button>
                    </form>
                </Card>

                <Card className="xl:col-span-2">
                    <CardHeader title="Historial de suscripciones" />
                    {subscriptions.length === 0 ? (
                        <p className="px-5 py-8 text-sm text-gray-500">
                            Sin historial.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-100 text-sm">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                            Plan
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                            Estado
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                            Inicio
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                            Fin
                                        </th>
                                        <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                            Importe
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {subscriptions.map((subscription) => (
                                        <tr key={subscription.id}>
                                            <td className="px-4 py-3">
                                                {subscription.plan?.name ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    tone={
                                                        subscription.status ===
                                                        'ACTIVE'
                                                            ? 'green'
                                                            : subscription.status ===
                                                                'TRIALING'
                                                              ? 'blue'
                                                              : 'gray'
                                                    }
                                                >
                                                    {subscription.status}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatDate(
                                                    subscription.starts_at,
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {subscription.ends_at
                                                    ? formatDate(
                                                          subscription.ends_at,
                                                      )
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {formatMoney(
                                                    subscription.amount,
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>

                <Card>
                    <CardHeader title="Auditoría de la empresa" />
                    {recentAudit.length === 0 ? (
                        <p className="px-5 py-8 text-sm text-gray-500">
                            Sin actividad registrada.
                        </p>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {recentAudit.map((log) => (
                                <div key={log.id} className="px-5 py-3 text-sm">
                                    <p className="font-medium text-gray-900">
                                        {log.description}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {log.user?.name ?? 'Sistema'} ·{' '}
                                        {formatDateTime(log.created_at)}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                </Card>
            </div>

            <div className="mt-6 text-center text-xs text-gray-400">
                Empresa #{tenant.id} · {show.url(tenant.id)}
            </div>
        </PlatformLayout>
    );
}
