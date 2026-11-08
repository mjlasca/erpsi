import { usePage } from '@inertiajs/react';

export function useCan(): (permission: string) => boolean {
    const can = (usePage().props.can ?? {}) as Record<string, boolean>;

    return (permission: string): boolean => Boolean(can[permission]);
}

export function useCanAny(): (permissions: string[]) => boolean {
    const can = useCan();

    return (permissions: string[]): boolean =>
        permissions.some((permission) => can(permission));
}
