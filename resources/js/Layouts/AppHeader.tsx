
import { User, ChevronDown } from 'lucide-react';

interface AppHeaderProps {
    name: string;
    avatarUrl?: string | null;
}

export default function AppHeader({
    name,
    avatarUrl,
}: AppHeaderProps) {
    const initials = name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(part => part[0])
        .join('')
        .toUpperCase();

    return (
        <header className="flex h-16 items-center justify-end border-b border-gray-200 bg-white px-6">
            <button
                type="button"
                className="flex items-center gap-3 rounded-lg px-2 py-1.5 transition-colors hover:bg-gray-50"
            >
                {avatarUrl ? (
                    <img
                        src={avatarUrl}
                        alt={name}
                        className="h-9 w-9 rounded-full object-cover"
                    />
                ) : (
                    <div className="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                        {initials || <User size={18} />}
                    </div>
                )}

                <span className="text-sm font-medium text-gray-700">
                    {name}
                </span>

                <ChevronDown size={16} className="text-gray-400" />
            </button>
        </header>
    );
}
