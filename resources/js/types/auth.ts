export type User = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    avatar?: string | null;
    role?: {
        id: number;
        name: string;
    } | null;
    permissions?: string[];
    email_verified_at?: string | null;
    created_at?: string;
    updated_at?: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
};
