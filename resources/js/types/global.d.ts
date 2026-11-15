import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            can?: Record<string, boolean>;
            locale?: string;
            timezone?: string;
            currency?: string;
            supportedLocales?: string[];
            tenant?: {
                id: number;
                name: string;
                slug: string;
                locale: string;
                timezone: string;
                currency: string;
                logo_url: string | null;
            } | null;
            tenants?: Array<{ id: number; name: string }>;
            supportMode?: boolean;
            supportTenant?: { id: number; name: string } | null;
            flash?: {
                success?: string | null;
                error?: string | null;
            };
            [key: string]: unknown;
        };
    }
}
