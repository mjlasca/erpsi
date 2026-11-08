import { Menu } from 'lucide-react';
import UserDropdown from '@/Components/Ui/UserDropdown';

interface AppHeaderProps {
    name: string;
    avatarUrl?: string | null;
    onMenuClick: () => void;
}

export default function AppHeader({
    name,
    avatarUrl,
    onMenuClick,
}: AppHeaderProps) {
    return (
        <header className="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 sm:px-6">
            <button
                type="button"
                aria-label="Abrir menú"
                onClick={onMenuClick}
                className="rounded-lg p-2 text-gray-600 hover:bg-gray-100 lg:hidden"
            >
                <Menu className="h-5 w-5" />
            </button>

            <div className="ml-auto">
                <UserDropdown name={name} avatarUrl={avatarUrl} />
            </div>
        </header>
    );
}
