import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppHeader from '@/Layouts/AppHeader';
import AppSidebar from '@/Layouts/AppSidebar';
import { Alert } from '@/Components/Ui/Primitives';
import { setFormattingSettings } from '@/lib/format';
import TenantSwitcher from '@/Components/Ui/TenantSwitcher';
import { leave as leaveSupport } from '@/routes/platform/support';

type LayoutProps = {
    auth?: {
        user?: {
            name: string;
            avatar?: string | null;
        } | null;
    } | null;
    flash?: {
        success?: string | null;
        error?: string | null;
    };
    locale?: string;
    currency?: string;
    timezone?: string;
    tenant?: { id: number; name: string } | null;
    supportMode?: boolean;
    supportTenant?: { id: number; name: string } | null;
};

export default function ErpLayout({ children }: { children: ReactNode }) {
    const page = usePage();
    const props = page.props as unknown as LayoutProps;
    const user = props.auth?.user;
    const flash = props.flash;
    setFormattingSettings(
        props.locale ?? 'es',
        props.currency ?? 'MXN',
        props.timezone,
    );
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(false);

    useEffect(() => {
        setSidebarOpen(false);
    }, [page.url]);

    return (
        <div className="flex min-h-screen bg-gray-50">
            <AppSidebar
                mobileOpen={sidebarOpen}
                collapsed={sidebarCollapsed}
                onClose={() => setSidebarOpen(false)}
                onToggleCollapsed={() =>
                    setSidebarCollapsed((collapsed) => !collapsed)
                }
            />

            <div className="flex min-w-0 flex-1 flex-col">
                <AppHeader
                    name={user?.name ?? 'Usuario'}
                    avatarUrl={user?.avatar}
                    onMenuClick={() => setSidebarOpen(true)}
                    tenant={props.tenant}
                />
                <div className="hidden border-b border-gray-100 bg-white px-6 py-2 lg:block">
                    <TenantSwitcher />
                </div>

                <main className="flex-1 overflow-y-auto p-4 sm:p-6">
                    {props.supportMode && props.supportTenant && (
                        <div className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            <p>
                                Modo soporte activo en{' '}
                                <strong>{props.supportTenant.name}</strong>. Las
                                acciones quedan auditadas.
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
                    {flash?.success && (
                        <Alert tone="success">{flash.success}</Alert>
                    )}
                    {flash?.error && <Alert tone="error">{flash.error}</Alert>}

                    <div className="mx-auto max-w-7xl">{children}</div>
                </main>
            </div>
        </div>
    );
}
