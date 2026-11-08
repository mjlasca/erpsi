import type { ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import AppHeader from '@/Layouts/AppHeader';
import AppSidebar from '@/Layouts/AppSidebar';
import { Alert } from '@/Components/Ui/Primitives';

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
};

export default function ErpLayout({ children }: { children: ReactNode }) {
    const page = usePage();
    const props = page.props as unknown as LayoutProps;
    const user = props.auth?.user;
    const flash = props.flash;

    return (
        <div className="flex min-h-screen bg-gray-50">
            <AppSidebar />

            <div className="flex min-w-0 flex-1 flex-col">
                <AppHeader
                    name={user?.name ?? 'Usuario'}
                    avatarUrl={user?.avatar}
                />

                <main className="flex-1 overflow-y-auto p-4 sm:p-6">
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
