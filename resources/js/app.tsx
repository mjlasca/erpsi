import type { ResolvedComponent } from '@inertiajs/react';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const pages = import.meta.glob<{
    default: ResolvedComponent;
}>('./Pages/**/*.tsx');

const aliases: Record<string, string> = {
    dashboard: './Pages/DashBoard/Index.tsx',
    'auth/login': './Pages/Auth/Login/Index.tsx',
};

function pascalize(segment: string): string {
    return segment
        .split('-')
        .map(
            (part) =>
                part.charAt(0).toUpperCase() + part.slice(1).toLowerCase(),
        )
        .join('');
}

function pageCandidates(name: string): string[] {
    if (name.endsWith('/Index')) {
        return [`./Pages/${name}.tsx`];
    }

    const path = name.split('/').map(pascalize).join('/');

    return [
        `./Pages/${path}.tsx`,
        `./Pages/${path}/Index.tsx`,
        `./Pages/${name}.tsx`,
        `./Pages/${name}/Index.tsx`,
    ];
}

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),

    progress: {
        color: '#4B5563',
    },

    resolve: (name) => {
        const candidates = [
            aliases[name.toLowerCase()],
            ...pageCandidates(name),
        ].filter((candidate): candidate is string => Boolean(candidate));

        const key = candidates.find((candidate) => candidate in pages);

        if (!key) {
            throw new Error(`No se encontró la página de Inertia: ${name}`);
        }

        return resolvePageComponent(key, pages).then(
            (module) => module.default,
        );
    },

    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
