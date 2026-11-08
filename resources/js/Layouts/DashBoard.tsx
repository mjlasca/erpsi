import type { ReactNode } from 'react';
import AppSidebar from '@/Layouts/AppSidebar';
import AppHeader from '@/Layouts/AppHeader';

interface DashboardLayoutProps {
    children: ReactNode;
    user: {
        name: string;
        avatarUrl?: string | null;
    };
}

export default function DashboardLayout({
    children,
    user,
}: DashboardLayoutProps) {
    return (
        <div className="flex min-h-screen bg-gray-50">
            <AppSidebar />

            <div className="flex min-w-0 flex-1 flex-col">
                <AppHeader name={user.name} avatarUrl={user.avatarUrl} />

                <main className="flex-1 overflow-y-auto p-6">{children}</main>
            </div>
        </div>
    );
}
