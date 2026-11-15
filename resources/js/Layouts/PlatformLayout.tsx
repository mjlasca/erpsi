import type { ReactNode } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Building2,
    ChevronLeft,
    CreditCard,
    Gauge,
    LogOut,
    ScrollText,
    Settings,
    Users,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { dashboard as erpDashboard, logout } from '@/routes';
import { dashboard } from '@/routes/platform';
import { index as tenantsIndex } from '@/routes/platform/tenants';
import { index as plansIndex } from '@/routes/platform/plans';
import { index as subscriptionsIndex } from '@/routes/platform/subscriptions';
import { index as usersIndex } from '@/routes/platform/users';
import { index as auditIndex } from '@/routes/platform/audit';
import { leave as leaveSupport } from '@/routes/platform/support';
import { edit as settingsEdit } from '@/routes/platform/settings';

const navigation = [
    { label: 'Resumen', href: dashboard.url(), icon: Gauge },
    { label: 'Empresas', href: tenantsIndex.url(), icon: Building2 },
    { label: 'Planes', href: plansIndex.url(), icon: CreditCard },
    {
        label: 'Suscripciones',
        href: subscriptionsIndex.url(),
        icon: CreditCard,
    },
    { label: 'Usuarios', href: usersIndex.url(), icon: Users },
    { label: 'Auditoría', href: auditIndex.url(), icon: ScrollText },
    { label: 'Configuración', href: settingsEdit.url(), icon: Settings },
];

export default function PlatformLayout({ children }: { children: ReactNode }) {
    const page = usePage();
    const { url } = page;
    const props = page.props as unknown as {
        auth?: { user?: { name: string; email: string } | null };
        flash?: { success?: string | null; error?: string | null };
        supportMode?: boolean;
        supportTenant?: { id: number; name: string } | null;
    };
    const user = props.auth?.user;

    return (
        <div className="min-h-screen bg-gray-100">
            <header className="border-b border-gray-800 bg-gray-950 text-white">
                <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
                    <Link
                        href={dashboard.url()}
                        className="flex items-center gap-2 font-semibold"
                    >
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-sm">
                            ER
                        </span>
                        <span>ERPsi · Plataforma</span>
                    </Link>

                    <div className="flex items-center gap-3 text-sm">
                        <span className="hidden text-gray-400 sm:inline">
                            {user?.name}
                        </span>
                        <Link
                            href={erpDashboard.url()}
                            className="inline-flex items-center gap-1 rounded-lg border border-gray-700 px-3 py-1.5 text-gray-300 hover:bg-gray-800"
                        >
                            <ChevronLeft className="h-4 w-4" />
                            Volver al ERP
                        </Link>
                        <button
                            type="button"
                            onClick={() => router.post(logout.url())}
                            className="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-gray-400 hover:bg-gray-800 hover:text-white"
                        >
                            <LogOut className="h-4 w-4" />
                            Salir
                        </button>
                    </div>
                </div>

                <nav className="mx-auto flex max-w-7xl gap-1 overflow-x-auto px-4 pb-2 sm:px-6">
                    {navigation.map((item) => {
                        const active =
                            item.href === '/platform'
                                ? url === '/platform'
                                : url.startsWith(item.href);
                        const Icon = item.icon;

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={cn(
                                    'inline-flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                    active
                                        ? 'bg-blue-600 text-white'
                                        : 'text-gray-400 hover:bg-gray-800 hover:text-white',
                                )}
                            >
                                <Icon className="h-4 w-4" />
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>
            </header>

            <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6">
                {props.supportMode && props.supportTenant && (
                    <div className="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <p>
                            Modo soporte activo en{' '}
                            <strong>{props.supportTenant.name}</strong>. Las
                            acciones realizadas quedan auditadas.
                        </p>
                        <button
                            type="button"
                            onClick={() => router.post(leaveSupport.url())}
                            className="rounded-lg bg-amber-900 px-3 py-1.5 font-medium text-white hover:bg-amber-800"
                        >
                            Salir del modo soporte
                        </button>
                    </div>
                )}

                {props.flash?.success && (
                    <div className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {props.flash.success}
                    </div>
                )}
                {props.flash?.error && (
                    <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {props.flash.error}
                    </div>
                )}

                {children}
            </main>
        </div>
    );
}
