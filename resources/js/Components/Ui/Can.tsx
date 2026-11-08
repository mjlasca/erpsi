import type { ReactNode } from 'react';
import { useCan } from '@/lib/permissions';

export default function Can({
    permission,
    children,
    fallback = null,
}: {
    permission: string;
    children: ReactNode;
    fallback?: ReactNode;
}) {
    const can = useCan();

    return <>{can(permission) ? children : fallback}</>;
}
